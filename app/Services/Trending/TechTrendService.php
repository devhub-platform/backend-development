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

class TechTrendService
{
    public  const BASE_CACHE_KEY = 'tech_trending:shared';
    private const BASE_CACHE_TTL = 30; // minutes
    private const USER_CACHE_TTL = 5;  // minutes
    private const MAX_PER_SOURCE = 5;

    private const REJECT_KEYWORDS = [
        // Community/social noise
        'win this week', 'week recap', 'top 7 featured', 'introduce yourself',
        'what was your win', 'hiring', 'podcast', 'off topic', 'rant',
        'mental health', 'mental', 'career', 'burnout', 'productivity tips',
        'daily standup', 'good morning', 'coffee', 'life lesson', 'salary',
        'interview tips', 'motivat',
        // Personal blogging noise
        'monthly report', 'monthly dev', 'my journey', 'day 1 of', 'day 2 of',
        'day 3 of', 'day 4 of', 'day 5 of', 'day 6 of', 'day 7 of',
        'what i learned', 'build in public', 'my first app',
        'week 1', 'week 2', 'week 3', 'challenge inspired',
        'submit', 'submission for',
        // Opinion/lifestyle noise
        'i thought coding', 'lessons learned', 'thought coding',
        'freelance', 'mindset', 'impostor', 'imposter',
        'work life', 'work-life', 'side hustle',
    ];

    // DEV.to tags that indicate non-technical content
    private const REJECT_TAGS = [
        'career', 'motivation', 'learning', 'freelance',
        'mindset', 'journey', 'discuss', 'watercooler',
        'beginners', 'codenewbie', 'devjournal',
    ];

    public function __construct(
        private EmbeddingService       $embedding,
        private TrendEnrichmentService $enrichment,
        private TopicDetector          $topicDetector,
        private FeedMixer              $feedMixer,
    ) {}

    // ─── Public Entry Point ───────────────────────────────────────────────────

    public function getTechTrends(): array
    {
        $userTopics = $this->getUserTopics();
        $userId     = Auth::id();

        $shared = $this->getSharedTrends();

        if (empty($userTopics)) {
            return $shared;
        }

        $topicsHash = md5(implode(',', $userTopics));
        $cacheKey   = 'tech_trending:user:' . $userId . ':' . $topicsHash;

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(self::USER_CACHE_TTL),
            fn() => $this->personalizeShared($shared, $userTopics)
        );
    }

    /**
     * Build or return the shared feed — NO Gemini here.
     * Fast, lightweight, cached for 30 minutes.
     * Warmed by cron job every 30 minutes.
     */
    public function getSharedTrends(): array
    {
        return Cache::remember(
            self::BASE_CACHE_KEY,
            now()->addMinutes(self::BASE_CACHE_TTL),
            function () {
                $github = $this->fetchGitHub();
                $devto  = $this->fetchDevTo();
                $hn     = $this->fetchHackerNews();

                return $this->buildFeed($github, $devto, $hn);
            }
        );
    }

    /**
     * Get a single trend item with AI enrichment.
     * Called by GET /tech-trends/{id}
     * Returns cached AI data instantly, or fallback + triggers background enrichment.
     */
    public function getTrendById(string $id): ?array
    {
        $trends = $this->getSharedTrends();

        // Find the item by its cache key id
        $item = collect($trends)->firstWhere('id', $id);

        if (!$item) return null;

        // Check if AI enrichment is already cached
        $aiCacheKey = $this->enrichment->getKey($item);
        $cached     = Cache::get($aiCacheKey);

        if ($cached) {
            // AI ready → return full item instantly
            return array_merge($item, $cached);
        }

        // AI not ready → trigger background enrichment via defer()
        defer(fn() => $this->enrichment->enrichSingle($item));

        // Return item with fallback AI fields
        $fallback = $this->enrichment->getFallback($item);

        return array_merge($item, $fallback, ['ai_ready' => false]);
    }

    // ─── Personalization ──────────────────────────────────────────────────────

    private function personalizeShared(array $items, array $userTopics): array
    {
        $rescored = array_map(function ($item) use ($userTopics) {
            $boost         = $this->keywordBoostFallback($item, $userTopics);
            $item['score'] = ($item['score'] ?? 0) + ($boost * 0.2);
            return $item;
        }, $items);

        usort($rescored, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return $rescored;
    }

    // ─── Feed Pipeline (NO AI enrichment here) ────────────────────────────────

    private function buildFeed(array $github, array $devto, array $hn): array
    {
        $github = $this->normalizeStats($github);
        $devto  = $this->normalizeStats($devto);
        $hn     = $this->normalizeStats($hn);

        $all = array_merge($github, $devto, $hn);

        if (empty($all)) return [];

        // 1. Compute embeddings (cached 7 days — zero API calls after first run)
        $all = array_map(function ($item) {
            $embKey = 'tech:emb:' . md5(($item['title'] ?? '') . ($item['description'] ?? ''));

            $item['embedding'] = Cache::remember(
                $embKey,
                now()->addDays(7),
                fn() => $this->embedding->embed(
                    ($item['title'] ?? '') . ' ' . ($item['description'] ?? '')
                )
            );

            return $item;
        }, $all);

        // 2. Semantic deduplication — removes same story from different sources
        //    Falls back to title-based dedup for items without embedding
        $withEmb = array_values(array_filter($all, fn($i) => !empty($i['embedding'])));
        $without = array_values(array_filter($all, fn($i) =>  empty($i['embedding'])));

        $withEmb = $this->embedding->deduplicate($withEmb, 0.88);
        $without = $this->deduplicateByTitle($without);

        $deduped = array_merge($withEmb, $without);

        // 2. Topic detection
        $withTopics = $this->topicDetector->detectBatch($deduped);

        // 3. Score
        $scored = array_map(function ($item) {
            $item['score'] = $this->calculateScore($item, []);
            return $item;
        }, $withTopics);

        // 4. Diversity mixing
        $mixed = $this->feedMixer->mixWithSourceDiversity($scored);

        // 5. Final mapping — lightweight, no AI fields
        return array_map(function ($item) {
            unset($item['embedding']);
            return [
                'id'          => md5(($item['source'] ?? '') . '|' . ($item['title'] ?? '')),
                'source'      => $item['source']      ?? null,
                'title'       => $item['title']       ?? null,
                'description' => $item['description'] ?? null,
                'url'         => $item['url']          ?? null,
                'stats'       => $item['stats']       ?? 0,
                'topic'       => $item['topic']       ?? 'general',
                'score'       => $item['score']       ?? 0,
                'tags'        => $item['tags']        ?? [],
            ];
        }, $mixed);
    }

    // ─── Title-based Deduplication ───────────────────────────────────────────

    private function deduplicateByTitle(array $items): array
    {
        $seen = [];

        return array_values(array_filter($items, function ($item) use (&$seen) {
            $key = strtolower(preg_replace('/\s+/', ' ', trim($item['title'] ?? '')));
            $key = substr($key, 0, 50);

            foreach ($seen as $s) {
                similar_text($key, $s, $percent);
                if ($percent >= 70) return false;
            }

            $seen[] = $key;
            return true;
        }));
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

        // Use pre-computed source-relative normalized stats (0→1 within same source)
        $normalized = ($item['normalized_stats'] ?? 0) * $sourceWeight;

        $recency = 0.5;
        if (!empty($item['created_at'])) {
            try {
                $hours   = max(now()->diffInHours(Carbon::parse($item['created_at'])), 1);
                $recency = exp(-$hours / 24);
            } catch (\Exception) {
                $recency = 0.5;
            }
        }

        $boost = empty($userTopics) ? 0.0 : $this->keywordBoostFallback($item, $userTopics);

        return ($normalized * 0.5) + ($recency * 0.3) + ($boost * 0.2);
    }

    private function keywordBoostFallback(array $item, array $userTopics): float
    {
        $text     = strtolower(($item['title'] ?? '') . ' ' . ($item['description'] ?? '') . ' ' . implode(' ', $item['tags'] ?? []));
        $itemTags = array_map('strtolower', $item['tags'] ?? []);
        $boost    = 0.0;

        foreach ($userTopics as $topic) {
            $topic = strtolower($topic);
            if (in_array($topic, $itemTags))  { $boost += 0.5; continue; }
            if (str_contains($text, $topic))  { $boost += 0.3; continue; }
            foreach (explode(' ', $topic) as $word) {
                if (strlen($word) > 2 && preg_match('/\b' . preg_quote($word, '/') . '\b/i', $text)) {
                    $boost += 0.1;
                }
            }
        }

        return min($boost, 0.8);
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
                    'q'        => "stars:>50 pushed:>{$since} created:>2020-01-01 has:description",
                    'sort'     => 'updated',
                    'order'    => 'desc',
                    'per_page' => 15,
                ]);

            if (!$response->successful()) {
                Log::warning('GitHub trending API failed', ['status' => $response->status()]);
                return [];
            }

            return collect($response->json('items', []))
                ->filter(fn($r) => !empty(trim($r['description'] ?? '')) && !empty($r['language']))
                ->map(function ($repo) {
                    $desc = strtolower($repo['description'] ?? '');
                    $tags = array_values(array_filter([
                        strtolower($repo['language'] ?? ''),
                        str_contains($desc, 'ai')       ? 'ai'       : null,
                        str_contains($desc, 'security') ? 'security' : null,
                        str_contains($desc, 'api')      ? 'api'      : null,
                    ]));

                    // Velocity = stars per day since creation
                    // This ranks repos gaining stars fast over all-time giants
                    $createdAt   = $repo['created_at'] ?? null;
                    $ageInDays   = $createdAt
                        ? max(1, now()->diffInDays(\Carbon\Carbon::parse($createdAt)))
                        : 365;
                    $stars       = (int) ($repo['stargazers_count'] ?? 0);
                    $velocity    = (int) ($stars / $ageInDays);

                    return [
                        'source'      => 'github',
                        'title'       => $repo['full_name']      ?? '',
                        'description' => $repo['description']    ?? '',
                        'author'      => $repo['owner']['login'] ?? '',
                        'language'    => $repo['language']       ?? null,
                        'stats'       => $velocity, // velocity instead of raw stars
                        'url'         => $repo['html_url']       ?? '',
                        'created_at'  => $repo['pushed_at']      ?? null,
                        'tags'        => $tags,
                    ];
                })
                ->sortByDesc('stats')
                ->take(self::MAX_PER_SOURCE)
                ->values()->toArray();

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
                    'title'       => $a['title']                           ?? '',
                    'description' => $a['description']                     ?? '',
                    'author'      => $a['user']['name']                    ?? '',
                    'stats'       => (int) ($a['positive_reactions_count'] ?? 0),
                    'url'         => $a['url']                             ?? '',
                    'created_at'  => $a['published_at']                    ?? null,
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

        // Reject by title keywords
        foreach (self::REJECT_KEYWORDS as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $title)) return false;
        }

        // Reject if ALL tags are noise tags (allow if at least one real tech tag)
        $tags        = array_map('strtolower', $article['tag_list'] ?? []);
        $noisyTags   = array_intersect($tags, self::REJECT_TAGS);
        $techTags    = array_diff($tags, self::REJECT_TAGS);

        if (!empty($noisyTags) && empty($techTags)) return false;

        return ($article['positive_reactions_count'] ?? 0) >= 15;
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
            )->all()
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
