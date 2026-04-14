<?php

namespace App\Services\Trending;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TechTrendService
{
    private const CACHE_KEY = 'tech_trending';
    private const CACHE_TTL = 60; // minutes

    private const REJECT_KEYWORDS = [
        'win this week', 'week recap', 'top 7 featured', 'introduce yourself',
        'what was your win', 'hiring', 'podcast', 'off topic', 'rant',
        'motivat', 'mental health', 'mental', 'career', 'burnout',
        'productivity tips', 'daily standup', 'good morning', 'coffee',
        'life lesson', 'salary', 'interview tips',
    ];

    // ─── Public ───────────────────────────────────────────────────────────────

    public function getTechTrends(): array
    {
        $userTopics = $this->getUserTopics();

        // Cache key per user if they have topics, otherwise shared cache
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

    // ─── Ranking ──────────────────────────────────────────────────────────────

    private function rankAndMix(array $github, array $devto, array $hn, array $userTopics): array
    {
        $all = collect(array_merge($github, $devto, $hn));

        if ($all->isEmpty()) {
            return [];
        }

        $maxStats = max($all->max('stats'), 1);

        return $all->map(function ($item) use ($maxStats, $userTopics) {
            $item['score'] = $this->calculateScore($item, $maxStats, $userTopics);
            return $item;
        })
            ->sortByDesc('score')
            ->take(15)
            ->values()
            ->toArray();
    }

    private function calculateScore(array $item, int $maxStats, array $userTopics): float
    {
        // 1. Normalized engagement (0 → 1)
        $normalized = $maxStats > 0 ? $item['stats'] / $maxStats : 0;

        // 2. Recency score — newer = higher
        $recency = 0.5; // default if no date
        if (!empty($item['created_at'])) {
            $hours   = max(now()->diffInHours($item['created_at']), 0);
            $recency = 1 / ($hours + 2);
        }

        // 3. Base score
        $base = ($normalized * 0.7) + ($recency * 0.3);

        // 4. Personalization boost from user topics
        $boost = $this->personalizeBoost($item, $userTopics);

        return $base + $boost;
    }

    private function personalizeBoost(array $item, array $userTopics): float
    {
        if (empty($userTopics)) {
            return 0.0;
        }

        $title = strtolower($item['title'] ?? '');
        $tags  = array_map('strtolower', $item['tags'] ?? []);

        foreach ($userTopics as $topic) {
            // Match against tags first (exact)
            if (in_array($topic, $tags)) {
                return 0.3;
            }

            // Match against title (keyword)
            foreach (explode(' ', $topic) as $word) {
                if (strlen($word) > 2 && str_contains($title, $word)) {
                    return 0.2;
                }
            }
        }

        return 0.0;
    }

    // ─── User Topics ──────────────────────────────────────────────────────────

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

    // ─── GitHub ───────────────────────────────────────────────────────────────

    private function fetchGitHub(): array
    {
        try {
            $response = Http::withToken(config('services.github.token'))
                ->timeout(10)
                ->retry(2, 500)
                ->get('https://api.github.com/search/repositories', [
                    'q'        => 'stars:>100 pushed:>' . now()->subDays(7)->toDateString() . ' has:description',
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
                ->take(5)
                ->map(fn($repo) => [
                    'source'      => 'github',
                    'title'       => e($repo['full_name'] ?? ''),
                    'description' => e($repo['description'] ?? ''),
                    'author'      => e($repo['owner']['login'] ?? ''),
                    'language'    => e($repo['language'] ?? 'Unknown'),
                    'stats'       => (int) ($repo['stargazers_count'] ?? 0),
                    'url'         => e($repo['html_url'] ?? ''),
                    'created_at'  => $repo['pushed_at'] ?? null,
                    'tags'        => array_filter([strtolower($repo['language'] ?? '')]),
                ])
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            Log::error('GitHub trending fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function isValidGithubRepo(array $repo): bool
    {
        if (empty(trim($repo['description'] ?? ''))) return false;
        if (empty($repo['language'])) return false;
        return true;
    }

    // ─── DEV.to ───────────────────────────────────────────────────────────────

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
                ->take(5)
                ->map(fn($article) => [
                    'source'      => 'devto',
                    'title'       => e($article['title'] ?? ''),
                    'description' => e($article['description'] ?? ''),
                    'author'      => e($article['user']['name'] ?? ''),
                    'stats'       => (int) ($article['positive_reactions_count'] ?? 0),
                    'url'         => e($article['url'] ?? ''),
                    'created_at'  => $article['published_at'] ?? null,
                    'tags'        => collect($article['tag_list'] ?? [])->take(3)->toArray(),
                ])
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            Log::error('DEV.to trending fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function isValidDevToArticle(array $article): bool
    {
        $title = strtolower($article['title'] ?? '');

        foreach (self::REJECT_KEYWORDS as $keyword) {
            if (str_contains($title, $keyword)) return false;
        }

        if (($article['positive_reactions_count'] ?? 0) < 10) return false;

        return true;
    }

    // ─── HackerNews ───────────────────────────────────────────────────────────

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

            return collect($response->json())
                ->take(20)
                ->map(function ($id) {
                    try {
                        $item = Http::timeout(5)
                            ->get("https://hacker-news.firebaseio.com/v0/item/{$id}.json")
                            ->json();

                        if (!$this->isValidHackerNewsItem($item)) return null;

                        return [
                            'source'      => 'hackernews',
                            'title'       => e($item['title'] ?? ''),
                            'description' => e($item['text'] ?? ''),
                            'author'      => e($item['by'] ?? ''),
                            'stats'       => (int) ($item['score'] ?? 0),
                            'url'         => e($item['url'] ?? "https://news.ycombinator.com/item?id={$id}"),
                            'created_at'  => isset($item['time']) ? now()->subSeconds(now()->timestamp - $item['time'])->toIso8601String() : null,
                            'tags'        => $this->extractTechKeywords($item['title'] ?? ''),
                        ];
                    } catch (\Exception $e) {
                        Log::warning('HackerNews item fetch failed', ['id' => $id, 'error' => $e->getMessage()]);
                        return null;
                    }
                })
                ->filter()
                ->take(5)
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            Log::error('HackerNews trending fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function isValidHackerNewsItem(?array $item): bool
    {
        if (empty($item)) return false;
        if (empty($item['url'])) return false;
        if (($item['score'] ?? 0) < 50) return false;

        $title = strtolower($item['title'] ?? '');

        // Must contain at least one tech keyword
        if (empty($this->extractTechKeywords($title))) return false;

        foreach (self::REJECT_KEYWORDS as $keyword) {
            if (str_contains($title, $keyword)) return false;
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

        $text = strtolower($text);

        return array_values(array_filter($keywords, fn($k) => str_contains($text, $k)));
    }
}
