<?php

namespace App\Services\Trending;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TechTrendService
{
    private const CACHE_KEY = 'tech_trending';
    private const CACHE_TTL = 60; // minutes
    private const MAX_PER_SOURCE = 5;

    /**
     * Keywords that indicate non-technical content.
     * Uses word-boundary matching to avoid false positives.
     */
    private const REJECT_KEYWORDS = [
        'win this week', 'week recap', 'top 7 featured', 'introduce yourself',
        'what was your win', 'hiring', 'podcast', 'off topic', 'rant',
        'mental health', 'mental', 'career', 'burnout', 'productivity tips',
        'daily standup', 'good morning', 'coffee', 'life lesson', 'salary',
        'interview tips', 'motivat',
    ];

    // ─── Public Entry Point ───────────────────────────────────────────────────

    /**
     * Fetch, score, and return a ranked list of trending tech items.
     *
     * Results are cached globally for anonymous users and per-user
     * for authenticated users with personalization topics.
     */
    public function getTechTrends(): array
    {
        $userTopics = $this->getUserTopics();

        // Personalized cache per user; shared cache for guests
        $cacheKey = empty($userTopics)
            ? self::CACHE_KEY
            : self::CACHE_KEY . ':user:' . Auth::id();

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($userTopics) {
            $github = $this->fetchGitHub();
            $devto  = $this->fetchDevTo();
            $hn     = $this->fetchHackerNews();

            return $this->rankAndMix($github, $devto, $hn, $userTopics);
        });
    }

    // ─── Ranking & Mixing ─────────────────────────────────────────────────────

    /**
     * Normalize, score, and merge results from all three sources.
     *
     * Normalization is done per-source to avoid scale bias
     * (GitHub stars can reach 400k while DEV.to reactions rarely exceed 500).
     * Source diversity is enforced by capping each source at MAX_PER_SOURCE.
     */
    private function rankAndMix(array $github, array $devto, array $hn, array $userTopics): array
    {
        // Normalize stats within each source independently
        $github = $this->normalizeStats($github);
        $devto  = $this->normalizeStats($devto);
        $hn     = $this->normalizeStats($hn);

        $all = collect(array_merge($github, $devto, $hn));

        if ($all->isEmpty()) {
            return [];
        }

        return $all
            ->map(function ($item) use ($userTopics) {
                // Enrich item with generated content fields
                $item['content'] = $this->expandContent($item);
                // Compute final ranking score
                $item['score']   = $this->calculateScore($item, $userTopics);
                return $item;
            })
            ->sortByDesc('score')
            // Enforce source diversity: cap each source to MAX_PER_SOURCE items
            ->groupBy('source')
            ->flatMap(fn($group) => $group->take(self::MAX_PER_SOURCE))
            ->sortByDesc('score')
            ->take(15)
            ->values()
            ->toArray();
    }

    /**
     * Normalize the 'stats' field within a single source to a 0–1 scale.
     * Prevents high-star GitHub repos from dominating DEV.to articles.
     */
    private function normalizeStats(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $max = max(array_column($items, 'stats'));

        if ($max === 0) {
            return $items;
        }

        return array_map(function ($item) use ($max) {
            $item['normalized_stats'] = $item['stats'] / $max;
            return $item;
        }, $items);
    }

    // ─── Scoring ──────────────────────────────────────────────────────────────

    /**
     * Calculate the final ranking score for a single item.
     *
     * Formula:
     *   normalized_engagement * 0.5
     *   + recency_score        * 0.3
     *   + personalization_boost * 0.2
     *
     * Source weight is applied via log-normalization to dampen outlier stats.
     */
    private function calculateScore(array $item, array $userTopics): float
    {
        // Source weight: GitHub > HackerNews > DEV.to (subjective editorial quality)
        $sourceWeight = match ($item['source']) {
            'github'      => 1.0,
            'hackernews'  => 0.9,
            'devto'       => 0.8,
            default       => 0.7,
        };

        // Log-scale normalization to reduce outlier dominance (e.g. 400k stars)
        $logStats  = log($item['stats'] + 1);
        $maxLog    = log(($item['stats'] + 1) * $sourceWeight + 1);
        $normalized = $maxLog > 0 ? ($logStats / $maxLog) * $sourceWeight : 0;

        // Recency decay: exp(-hours/24) — drops to ~0.37 after 24h, ~0.14 after 48h
        $recency = 0.5; // neutral fallback when date is unavailable
        if (!empty($item['created_at'])) {
            try {
                $hours   = max(now()->diffInHours(Carbon::parse($item['created_at'])), 1);
                $recency = exp(-$hours / 24);
            } catch (\Exception) {
                $recency = 0.5;
            }
        }

        $boost = $this->personalizeBoost($item, $userTopics);

        return ($normalized * 0.5)
            + ($recency   * 0.3)
            + ($boost     * 0.2);
    }

    /**
     * Compute a personalization boost based on the user's selected topics.
     *
     * Matching priority:
     *   1. Exact tag match         → +0.5
     *   2. Topic keyword in text   → +0.3
     *   3. Single word in text     → +0.1 per word
     *
     * Capped at 0.8 to prevent personalization from overwhelming quality signals.
     */
    private function personalizeBoost(array $item, array $userTopics): float
    {
        if (empty($userTopics)) {
            return 0.0;
        }

        $text = strtolower(
            ($item['title']       ?? '') . ' ' .
            ($item['description'] ?? '') . ' ' .
            implode(' ', $item['tags'] ?? [])
        );

        $itemTags = array_map('strtolower', $item['tags'] ?? []);
        $boost    = 0.0;

        foreach ($userTopics as $topic) {
            $topic = strtolower($topic);

            // Exact tag match (highest signal)
            if (in_array($topic, $itemTags)) {
                $boost += 0.5;
                continue;
            }

            // Full topic phrase found in text
            if (str_contains($text, $topic)) {
                $boost += 0.3;
                continue;
            }

            // Individual words within the topic phrase
            foreach (explode(' ', $topic) as $word) {
                if (strlen($word) > 2 && preg_match('/\b' . preg_quote($word, '/') . '\b/i', $text)) {
                    $boost += 0.1;
                }
            }
        }

        // Cap boost to prevent topic bias from overshadowing engagement quality
        return min($boost, 0.8);
    }

    // ─── Content Enrichment ───────────────────────────────────────────────────

    /**
     * Generate a structured content block for each trending item.
     *
     * Returns a rich description, trend rationale, potential impact,
     * and associated tech stack — all based on source-specific templates.
     */
    private function expandContent(array $item): array
    {
        $title  = $item['title']  ?? '';
        $source = $item['source'] ?? '';
        $author = $item['author'] ?? '';
        $stats  = $item['stats']  ?? 0;

        $lang     = $item['language'] ?? ($item['tags'][0] ?? null);
        $langText = (!empty($lang) && $lang !== 'general tech')
            ? $lang
            : 'modern software development';

        $openers = ["Recently,", "Currently,", "Right now,", "In the developer community,"];
        $prefix  = $openers[array_rand($openers)];

        $content = match ($source) {
            'github'      => "{$prefix} the project \"{$title}\" is gaining traction with around {$stats} stars on GitHub. Maintained by {$author}, it reflects growing developer interest in {$langText}.",
            'devto'       => "{$prefix} an article titled \"{$title}\" by {$author} is getting noticeable engagement with {$stats} reactions, highlighting practical insights in modern development.",
            'hackernews'  => "{$prefix} a discussion titled \"{$title}\" is trending on Hacker News with a score of {$stats}, actively debated by developers and engineers.",
            default       => "{$prefix} \"{$title}\" is attracting attention across the tech space.",
        };

        $whyTrending = match ($source) {
            'github'      => "Driven by continuous stars, forks, and developer contributions.",
            'devto'       => "Strong engagement and relevance among developers.",
            'hackernews'  => "Active discussion and upvotes from the tech community.",
            default       => "Increasing visibility in the tech ecosystem.",
        };

        $techStack = !empty($item['tags'])
            ? implode(', ', array_slice($item['tags'], 0, 3))
            : $langText;

        return [
            'content'      => $content,
            'why_trending' => $whyTrending,
            'impact'       => "This could influence developers working in {$langText}, especially in how tools, workflows, and architectures evolve.",
            'tech_stack'   => $techStack,
            'tags'         => $item['tags'] ?? [],
            'url'          => $item['url']  ?? null,
        ];
    }

    // ─── GitHub ───────────────────────────────────────────────────────────────

    /**
     * Fetch trending repositories from GitHub.
     *
     * Filters to repos pushed within the last 7 days with >100 stars.
     * Requires a description and a primary language to ensure content quality.
     */
    private function fetchGitHub(): array
    {
        try {
            $since    = now()->subDays(7)->toDateString();
            $response = Http::withToken(config('services.github.token'))
                ->timeout(10)
                ->retry(2, 500)
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
                ->filter(fn($repo) => $this->isValidGithubRepo($repo))
                ->take(self::MAX_PER_SOURCE)
                ->map(function ($repo) {
                    $desc = strtolower($repo['description'] ?? '');

                    // Derive secondary tags from description keywords
                    $tags = array_values(array_filter([
                        strtolower($repo['language'] ?? ''),
                        str_contains($desc, 'ai')       ? 'ai'       : null,
                        str_contains($desc, 'security') ? 'security' : null,
                        str_contains($desc, 'api')      ? 'api'      : null,
                    ]));

                    return [
                        'source'      => 'github',
                        'title'       => $repo['full_name']          ?? '',
                        'description' => $repo['description']        ?? '',
                        'author'      => $repo['owner']['login']     ?? '',
                        'language'    => $repo['language']           ?? null,
                        'stats'       => (int) ($repo['stargazers_count'] ?? 0),
                        'url'         => $repo['html_url']           ?? '',
                        'created_at'  => $repo['pushed_at']          ?? null,
                        'tags'        => $tags,
                    ];
                })
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            Log::error('GitHub trending fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * A valid GitHub repo must have a non-empty description and a primary language.
     * This filters out list repos, books, and non-code projects.
     */
    private function isValidGithubRepo(array $repo): bool
    {
        return !empty(trim($repo['description'] ?? '')) && !empty($repo['language']);
    }

    // ─── DEV.to ───────────────────────────────────────────────────────────────

    /**
     * Fetch top articles from DEV.to from the past 7 days.
     *
     * Uses a single broad request (no per-tag looping) for performance.
     * Filters out non-technical and low-engagement content.
     */
    private function fetchDevTo(): array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->get('https://dev.to/api/articles', [
                    'top'      => 7,
                    'per_page' => 20,
                ]);

            if (!$response->successful()) {
                Log::warning('DEV.to trending API failed', ['status' => $response->status()]);
                return [];
            }

            return collect($response->json())
                ->filter(fn($article) => $this->isValidDevToArticle($article))
                ->sortByDesc('positive_reactions_count')
                ->take(self::MAX_PER_SOURCE)
                ->map(fn($article) => [
                    'source'      => 'devto',
                    'title'       => $article['title']                    ?? '',
                    'description' => $article['description']              ?? '',
                    'author'      => $article['user']['name']             ?? '',
                    'stats'       => (int) ($article['positive_reactions_count'] ?? 0),
                    'url'         => $article['url']                      ?? '',
                    'created_at'  => $article['published_at']             ?? null,
                    'tags'        => collect($article['tag_list'] ?? [])->take(3)->toArray(),
                ])
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            Log::error('DEV.to trending fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Reject non-technical DEV.to articles using word-boundary matching.
     * Minimum reaction threshold ensures only engaged content is included.
     */
    private function isValidDevToArticle(array $article): bool
    {
        $title = $article['title'] ?? '';

        foreach (self::REJECT_KEYWORDS as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $title)) {
                return false;
            }
        }

        return ($article['positive_reactions_count'] ?? 0) >= 10;
    }

    // ─── HackerNews ───────────────────────────────────────────────────────────

    /**
     * Fetch top stories from HackerNews using concurrent HTTP requests.
     *
     * Uses Http::pool() to fetch multiple story details in parallel,
     * significantly reducing total wait time compared to sequential calls.
     */
    private function fetchHackerNews(): array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->get('https://hacker-news.firebaseio.com/v0/topstories.json');

            if (!$response->successful()) {
                Log::warning('HackerNews trending API failed', ['status' => $response->status()]);
                return [];
            }

            $ids = collect($response->json())->take(20)->values();

            // Concurrent requests — fetch all story details simultaneously
            $responses = Http::pool(fn($pool) =>
            $ids->map(fn($id) =>
            $pool->as((string) $id)
                ->timeout(5)
                ->get("https://hacker-news.firebaseio.com/v0/item/{$id}.json")
            )->toArray()
            );

            return $ids
                ->map(function ($id) use ($responses) {
                    try {
                        $item = $responses[(string) $id]?->json();

                        if (!$this->isValidHackerNewsItem($item)) {
                            return null;
                        }

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
                        Log::warning('HackerNews item fetch failed', [
                            'id'    => $id,
                            'error' => $e->getMessage(),
                        ]);
                        return null;
                    }
                })
                ->filter()
                ->take(self::MAX_PER_SOURCE)
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            Log::error('HackerNews trending fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * A valid HackerNews item must have a URL, a score above 50,
     * and at least one recognized tech keyword in its title.
     */
    private function isValidHackerNewsItem(?array $item): bool
    {
        if (empty($item) || empty($item['url'])) {
            return false;
        }

        if (($item['score'] ?? 0) < 50) {
            return false;
        }

        $title = $item['title'] ?? '';

        if (empty($this->extractTechKeywords($title))) {
            return false;
        }

        foreach (self::REJECT_KEYWORDS as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $title)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract recognized tech keywords from a text string.
     * Used to classify HackerNews stories as technical content.
     */
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

    // ─── User Topics ──────────────────────────────────────────────────────────

    /**
     * Retrieve the authenticated user's selected topics as lowercase strings.
     * Returns an empty array for guests or users with no topics.
     */
    private function getUserTopics(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        return $user->topics()
            ->where('is_active', true)
            ->pluck('name')
            ->map(fn($name) => strtolower(trim($name)))
            ->toArray();
    }
}
