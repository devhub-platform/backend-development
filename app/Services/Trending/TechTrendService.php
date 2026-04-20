<?php

namespace App\Services\Trending;

use App\Services\AI\TrendEnrichmentService;
use App\Services\AI\EmbeddingService;
use App\Services\AI\FeedMixer;
use App\Services\AI\TopicDetector;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TechTrendService — Upgraded with full AI layer
 *
 * What changed from the original:
 *  - TopicDetector replaces inline topic strings (zero AI cost)
 *  - EmbeddingService::deduplicate() removes near-duplicate items
 *  - AIContentService::generateTrendExplanation() enriches each item
 *    with summary / why_trending / impact (cached 6h per item)
 *  - FeedMixer replaces pure sortByDesc() with diversity-aware mixing
 *  - personalizeBoost() uses embedding similarity (degrades to keyword on failure)
 *  - Cache key bug fixed: null user never produces a user-specific key
 */
class TechTrendService
{
    private const CACHE_KEY     = 'tech_trending';
    private const CACHE_TTL     = 60;
    private const MAX_PER_SOURCE = 5;

    private const REJECT_KEYWORDS = [
        'win this week', 'week recap', 'top 7 featured', 'introduce yourself',
        'what was your win', 'hiring', 'podcast', 'off topic', 'rant',
        'mental health', 'mental', 'career', 'burnout', 'productivity tips',
        'daily standup', 'good morning', 'coffee', 'life lesson', 'salary',
        'interview tips', 'motivat',
    ];

    public function __construct(
        private EmbeddingService $embedding,
        private TrendEnrichmentService $enrichment,
        private TopicDetector    $topicDetector,
        private FeedMixer        $feedMixer,
    ) {}

    // ─── Public Entry Point ───────────────────────────────────────────────────

    public function getTechTrends(): array
    {
        $userTopics = $this->getUserTopics();
        $userId     = Auth::id();

        // Bug fix: null userId must never produce a user-scoped key
        $cacheKey = ($userId && !empty($userTopics))
            ? self::CACHE_KEY . ':user:' . $userId
            : self::CACHE_KEY;

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($userTopics) {
            $github = $this->fetchGitHub();
            $devto  = $this->fetchDevTo();
            $hn     = $this->fetchHackerNews();

            return $this->buildFeed($github, $devto, $hn, $userTopics);
        });
    }

    // ─── Feed Pipeline ────────────────────────────────────────────────────────

    /**
     * Full pipeline:
     *   normalize → deduplicate → detect topics → score → AI enrich → mix
     *
     * WHY this order:
     *   Dedup first reduces the pool before any AI calls.
     *   Topic detection is free (keywords), so it runs before scoring.
     *   AI enrichment runs last on the already-trimmed candidate set,
     *   keeping the number of LLM calls as small as possible (≤15).
     */
    private function buildFeed(array $github, array $devto, array $hn, array $userTopics): array
    {
        $github = $this->normalizeStats($github);
        $devto  = $this->normalizeStats($devto);
        $hn     = $this->normalizeStats($hn);

        $all = array_merge($github, $devto, $hn);

        if (empty($all)) {
            return [];
        }

        // 1. Semantic deduplication — uses cached embeddings, no wasted API calls
        $deduped = $this->embedding->deduplicate($all, 0.88);

        // 2. Keyword topic detection — zero cost
        $withTopics = $this->topicDetector->detectBatch($deduped);

        // 3. Score each item
        $scored = array_map(function ($item) use ($userTopics) {
            $item['score'] = $this->calculateScore($item, $userTopics);
            return $item;
        }, $withTopics);

        // 4. Batch AI enrichment — top 5 get ONE Gemini call, rest get template fallback
        $enriched = $this->enrichment->enrich($scored);

        // 5. Diversity-aware feed mixing
        $mixed = $this->feedMixer->mixWithSourceDiversity($enriched);

        // Strip embedding vectors from response — they are internal only
        return array_map(function ($item) {

            unset($item['embedding']);

            return [
                'source'        => $item['source'] ?? null,
                'title'         => $item['title'] ?? null,
                'description'   => $item['description'] ?? null,
                'url'           => $item['url'] ?? null,
                'stats'         => $item['stats'] ?? 0,
                'topic'         => $item['topic'] ?? 'general',
                'score'         => $item['score'] ?? 0,

                // AI GENERATED FIELDS
                'summary'       => $item['summary'] ?? null,
                'why_trending'  => $item['why_trending'] ?? null,
                'impact'        => $item['impact'] ?? null,
            ];

        }, $mixed);
    }

    // ─── Scoring ─────────────────────────────────────────────────────────────

    private function calculateScore(array $item, array $userTopics): float
    {
        $sourceWeight = match ($item['source']) {
            'github'     => 1.0,
            'hackernews' => 0.9,
            'devto'      => 0.8,
            default      => 0.7,
        };

        $logStats   = log($item['stats'] + 1);
        $maxLog     = log(($item['stats'] + 1) * $sourceWeight + 1);
        $normalized = $maxLog > 0 ? ($logStats / $maxLog) * $sourceWeight : 0;

        $recency = 0.5;
        if (!empty($item['created_at'])) {
            try {
                $hours   = max(now()->diffInHours(Carbon::parse($item['created_at'])), 1);
                $recency = exp(-$hours / 24);
            } catch (\Exception) {
                $recency = 0.5;
            }
        }

        $boost = $this->personalizeBoost($item, $userTopics);

        return ($normalized * 0.5) + ($recency * 0.3) + ($boost * 0.2);
    }

    /**
     * Personalization boost using embedding similarity.
     *
     * Falls back to keyword matching when the embedding API is unavailable,
     * so personalization never silently returns 0 for all users during outages.
     */
    private function personalizeBoost(array $item, array $userTopics): float
    {
        if (empty($userTopics)) {
            return 0.0;
        }

        $titleVec = $this->embedding->embed($item['title'] ?? '');

        if (empty($titleVec)) {
            return $this->keywordBoostFallback($item, $userTopics);
        }

        $boost = 0.0;

        foreach ($userTopics as $topic) {
            $topicVec = $this->embedding->embed($topic);

            if (empty($topicVec)) {
                continue;
            }

            $sim    = $this->embedding->cosine($titleVec, $topicVec);
            $boost += match (true) {
                $sim >= 0.85 => 0.5,
                $sim >= 0.70 => 0.3,
                $sim >= 0.50 => 0.1,
                default      => 0.0,
            };
        }

        return min($boost, 0.8);
    }

    private function keywordBoostFallback(array $item, array $userTopics): float
    {
        $text     = strtolower(($item['title'] ?? '') . ' ' . ($item['description'] ?? '') . ' ' . implode(' ', $item['tags'] ?? []));
        $itemTags = array_map('strtolower', $item['tags'] ?? []);
        $boost    = 0.0;

        foreach ($userTopics as $topic) {
            $topic = strtolower($topic);
            if (in_array($topic, $itemTags)) { $boost += 0.5; continue; }
            if (str_contains($text, $topic))  { $boost += 0.3; continue; }
            foreach (explode(' ', $topic) as $word) {
                if (strlen($word) > 2 && preg_match('/\b' . preg_quote($word, '/') . '\b/i', $text)) {
                    $boost += 0.1;
                }
            }
        }

        return min($boost, 0.8);
    }

    // ─── Content Enrichment (template fallback) ───────────────────────────────

    /**
     * Template-based content fields — used as fallback base before AI overlay.
     * AI enrichment overrides 'summary', 'why_trending', 'impact' from this array.
     */
    private function expandContent(array $item): array
    {
        $title    = $item['title']  ?? '';
        $source   = $item['source'] ?? '';
        $author   = $item['author'] ?? '';
        $stats    = $item['stats']  ?? 0;
        $lang     = $item['language'] ?? ($item['tags'][0] ?? null);
        $langText = (!empty($lang) && $lang !== 'general tech') ? $lang : 'modern software development';

        $openers = ["Recently,", "Currently,", "Right now,", "In the developer community,"];
        $prefix  = $openers[array_rand($openers)];

        $content = match ($source) {
            'github'     => "{$prefix} the project \"{$title}\" is gaining traction with around {$stats} stars on GitHub. Maintained by {$author}, it reflects growing developer interest in {$langText}.",
            'devto'      => "{$prefix} an article titled \"{$title}\" by {$author} is getting noticeable engagement with {$stats} reactions.",
            'hackernews' => "{$prefix} a discussion titled \"{$title}\" is trending on Hacker News with a score of {$stats}.",
            default      => "{$prefix} \"{$title}\" is attracting attention across the tech space.",
        };

        $techStack = !empty($item['tags']) ? implode(', ', array_slice($item['tags'], 0, 3)) : $langText;

        return [
            'content'      => $content,
            'summary'      => $content,  // will be overridden by AI
            'why_trending' => match ($source) {
                'github'     => "Driven by continuous stars, forks, and developer contributions.",
                'devto'      => "Strong engagement and relevance among developers.",
                'hackernews' => "Active discussion and upvotes from the tech community.",
                default      => "Increasing visibility in the tech ecosystem.",
            },
            'impact'       => "Could influence developers working in {$langText}.",
            'tech_stack'   => $techStack,
            'tags'         => $item['tags'] ?? [],
            'url'          => $item['url']  ?? null,
        ];
    }

    // ─── Stat Normalization ───────────────────────────────────────────────────

    private function normalizeStats(array $items): array
    {
        if (empty($items)) return [];

        $max = max(array_column($items, 'stats'));
        if ($max === 0) return $items;

        return array_map(function ($item) use ($max) {
            $item['normalized_stats'] = $item['stats'] / $max;
            return $item;
        }, $items);
    }

    // ─── GitHub ───────────────────────────────────────────────────────────────

    private function fetchGitHub(): array
    {
        try {
            $since    = now()->subDays(7)->toDateString();
            $response = Http::withToken(config('services.github.token'))
                ->timeout(10)->retry(2, 500)
                ->get('https://api.github.com/search/repositories', [
                    'q'        => "stars:>100 pushed:>{$since} has:description",
                    'sort'     => 'stars',
                    'order'    => 'desc',
                    'per_page' => 15,
                ]);

            if (!$response->successful()) {
                Log::warning('GitHub trending API failed', ['status' => $response->status()]);
                return [];
            }

            return collect($response->json('items', []))
                ->filter(fn($r) => !empty(trim($r['description'] ?? '')) && !empty($r['language']))
                ->take(self::MAX_PER_SOURCE)
                ->map(function ($repo) {
                    $desc = strtolower($repo['description'] ?? '');
                    $tags = array_values(array_filter([
                        strtolower($repo['language'] ?? ''),
                        str_contains($desc, 'ai')       ? 'ai'       : null,
                        str_contains($desc, 'security') ? 'security' : null,
                        str_contains($desc, 'api')      ? 'api'      : null,
                    ]));

                    return [
                        'source'      => 'github',
                        'title'       => $repo['full_name']      ?? '',
                        'description' => $repo['description']    ?? '',
                        'author'      => $repo['owner']['login'] ?? '',
                        'language'    => $repo['language']       ?? null,
                        'stats'       => (int) ($repo['stargazers_count'] ?? 0),
                        'url'         => $repo['html_url']       ?? '',
                        'created_at'  => $repo['pushed_at']      ?? null,
                        'tags'        => $tags,
                    ];
                })->values()->toArray();

        } catch (\Exception $e) {
            Log::error('GitHub fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ─── DEV.to ───────────────────────────────────────────────────────────────

    private function fetchDevTo(): array
    {
        try {
            $response = Http::timeout(10)->retry(2, 500)
                ->get('https://dev.to/api/articles', ['top' => 7, 'per_page' => 20]);

            if (!$response->successful()) {
                Log::warning('DEV.to API failed', ['status' => $response->status()]);
                return [];
            }

            return collect($response->json())
                ->filter(fn($a) => $this->isValidDevToArticle($a))
                ->sortByDesc('positive_reactions_count')
                ->take(self::MAX_PER_SOURCE)
                ->map(fn($a) => [
                    'source'      => 'devto',
                    'title'       => $a['title']                     ?? '',
                    'description' => $a['description']               ?? '',
                    'author'      => $a['user']['name']              ?? '',
                    'stats'       => (int) ($a['positive_reactions_count'] ?? 0),
                    'url'         => $a['url']                       ?? '',
                    'created_at'  => $a['published_at']              ?? null,
                    'tags'        => collect($a['tag_list'] ?? [])->take(3)->toArray(),
                ])->values()->toArray();

        } catch (\Exception $e) {
            Log::error('DEV.to fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function isValidDevToArticle(array $article): bool
    {
        $title = $article['title'] ?? '';
        foreach (self::REJECT_KEYWORDS as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $title)) return false;
        }
        return ($article['positive_reactions_count'] ?? 0) >= 10;
    }

    // ─── HackerNews ───────────────────────────────────────────────────────────

    private function fetchHackerNews(): array
    {
        try {
            $response = Http::timeout(10)->retry(2, 500)
                ->get('https://hacker-news.firebaseio.com/v0/topstories.json');

            if (!$response->successful()) {
                Log::warning('HackerNews API failed', ['status' => $response->status()]);
                return [];
            }

            $ids       = collect($response->json())->take(20)->values();
            $responses = Http::pool(fn($pool) =>
            $ids->map(fn($id) =>
            $pool->as((string) $id)->timeout(5)
                ->get("https://hacker-news.firebaseio.com/v0/item/{$id}.json")
            )->toArray()
            );

            return $ids->map(function ($id) use ($responses) {
                try {
                    $item = $responses[(string) $id]?->json();
                    if (!$this->isValidHackerNewsItem($item)) return null;

                    return [
                        'source'      => 'hackernews',
                        'title'       => $item['title'] ?? '',
                        'description' => $item['text']  ?? '',
                        'author'      => $item['by']    ?? '',
                        'stats'       => (int) ($item['score'] ?? 0),
                        'url'         => $item['url'] ?? "https://news.ycombinator.com/item?id={$id}",
                        'created_at'  => isset($item['time'])
                            ? Carbon::createFromTimestamp($item['time'])->toIso8601String()
                            : null,
                        'tags'        => $this->extractTechKeywords($item['title'] ?? ''),
                    ];
                } catch (\Exception $e) {
                    Log::warning('HN item failed', ['id' => $id, 'error' => $e->getMessage()]);
                    return null;
                }
            })->filter()->take(self::MAX_PER_SOURCE)->values()->toArray();

        } catch (\Exception $e) {
            Log::error('HackerNews fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function isValidHackerNewsItem(?array $item): bool
    {
        if (empty($item) || empty($item['url'])) return false;
        if (($item['score'] ?? 0) < 50) return false;
        if (empty($this->extractTechKeywords($item['title'] ?? ''))) return false;

        foreach (self::REJECT_KEYWORDS as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $item['title'] ?? '')) return false;
        }

        return true;
    }

    private function extractTechKeywords(string $text): array
    {
        $keywords = [
            'ai', 'ml', 'llm', 'gpt', 'api', 'cloud', 'security', 'web',
            'database', 'open source', 'linux', 'python', 'javascript',
            'typescript', 'rust', 'go', 'docker', 'kubernetes', 'devops',
            'backend', 'frontend', 'mobile', 'ios', 'android', 'framework',
            'library', 'github', 'software', 'programming', 'developer',
            'startup', 'saas', 'infrastructure', 'server', 'network',
        ];

        return array_values(array_filter(
            $keywords,
            fn($k) => preg_match('/\b' . preg_quote($k, '/') . '\b/i', $text)
        ));
    }

    // ─── User Topics ─────────────────────────────────────────────────────────

    private function getUserTopics(): array
    {
        $user = Auth::user();
        if (!$user) return [];

        return $user->topics()
            ->where('topics.is_active', true)
            ->pluck('topics.name')
            ->map(fn($n) => strtolower(trim($n)))
            ->toArray();
    }
}
