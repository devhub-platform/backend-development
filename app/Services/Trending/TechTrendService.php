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
        'win this week', 'week recap', 'top 7 featured', 'introduce yourself',
        'what was your win', 'hiring', 'podcast', 'off topic', 'rant',
        'mental health', 'mental', 'career', 'burnout', 'productivity tips',
        'daily standup', 'good morning', 'coffee', 'life lesson', 'salary',
        'interview tips', 'motivat',
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

        // 1. دايماً نجيب الـ shared cache الأول — fetch واحد بس لكل الناس
        $shared = $this->getSharedTrends();

        // 2. مفيش topics → ارجع الـ shared على طول
        if (empty($userTopics)) {
            return $shared;
        }

        // 3. في topics → re-score على الـ cached data من غير أي API calls
        return Cache::remember(
            'tech_trending:user:' . $userId,
            now()->addMinutes(self::USER_CACHE_TTL),
            fn() => $this->personalizeShared($shared, $userTopics)
        );
    }

    /**
     * Build or return the shared (non-personalized) feed.
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

    // ─── Personalization (zero API calls — pure in-memory) ────────────────────

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

    // ─── Feed Pipeline ────────────────────────────────────────────────────────

    private function buildFeed(array $github, array $devto, array $hn): array
    {
        $github = $this->normalizeStats($github);
        $devto  = $this->normalizeStats($devto);
        $hn     = $this->normalizeStats($hn);

        $all = array_merge($github, $devto, $hn);

        if (empty($all)) {
            return [];
        }

        // 1. Semantic deduplication
        $deduped = $this->embedding->deduplicate($all, 0.88);

        // 2. Keyword topic detection
        $withTopics = $this->topicDetector->detectBatch($deduped);

        // 3. Score each item
        $scored = array_map(function ($item) {
            $item['score'] = $this->calculateScore($item, []);
            return $item;
        }, $withTopics);

        // 4. Batch AI enrichment
        $enriched = $this->enrichment->enrich($scored);

        // 5. Diversity-aware feed mixing
        $mixed = $this->feedMixer->mixWithSourceDiversity($enriched);

        return array_map(function ($item) {
            unset($item['embedding']);
            return [
                'source'       => $item['source']       ?? null,
                'title'        => $item['title']        ?? null,
                'description'  => $item['description']  ?? null,
                'url'          => $item['url']           ?? null,
                'stats'        => $item['stats']        ?? 0,
                'topic'        => $item['topic']        ?? 'general',
                'score'        => $item['score']        ?? 0,
                'tags'         => $item['tags']         ?? [],
                'summary'      => $item['summary']      ?? null,
                'why_trending' => $item['why_trending'] ?? null,
                'impact'       => $item['impact']       ?? null,
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
                    'title'       => $a['title']                         ?? '',
                    'description' => $a['description']                   ?? '',
                    'author'      => $a['user']['name']                  ?? '',
                    'stats'       => (int) ($a['positive_reactions_count'] ?? 0),
                    'url'         => $a['url']                           ?? '',
                    'created_at'  => $a['published_at']                  ?? null,
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
