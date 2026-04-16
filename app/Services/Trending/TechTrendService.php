<?php

namespace App\Services\Trending;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TechTrendService
{
    private const CACHE_KEY = 'tech_trending';
    private const CACHE_TTL = 60;

    // ─────────────────────────────
    // MAIN ENTRY
    // ─────────────────────────────
    public function getTechTrends(): array
    {
        $userTopics = $this->getUserTopics();

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

    // ─────────────────────────────
    private function rankAndMix(array $github, array $devto, array $hn, array $userTopics): array
    {
        $all = collect(array_merge($github, $devto, $hn));

        if ($all->isEmpty()) return [];

        $maxStats = max($all->max('stats'), 1);

        return $all->map(function ($item) use ($maxStats, $userTopics) {

            $item['content'] = $this->expandContent($item);
            $item['score']   = $this->calculateScore($item, $maxStats, $userTopics);

            return $item;

        })
            ->sortByDesc('score')
            ->groupBy('source')
            ->flatMap(fn($g) => $g->take(5))
            ->take(15)
            ->values()
            ->toArray();
    }

    // ─────────────────────────────
    // 🔥 CONTENT FIXED
    // ─────────────────────────────
    private function expandContent(array $item): array
    {
        $title  = $item['title'] ?? '';
        $source = $item['source'] ?? '';
        $author = $item['author'] ?? '';
        $stats  = $item['stats'] ?? 0;

        // ✅ FIX language fallback
        $lang = $item['language']
            ?? ($item['tags'][0] ?? null);

        $langText = !empty($lang) && $lang !== 'general tech'
            ? $lang
            : 'modern software development';

        $openers = ["Recently,", "Currently,", "Right now,", "In the developer community,"];
        $prefix = $openers[array_rand($openers)];

        $content = match ($source) {

            'github' => "{$prefix} the project \"{$title}\" is gaining serious traction with around {$stats} stars on GitHub. Maintained by {$author}, it reflects growing developer interest in {$langText}, especially as more engineers adopt open-source tools to solve real-world problems.",

            'devto' => "{$prefix} an article titled \"{$title}\" by {$author} is getting noticeable engagement among developers. With {$stats} reactions, it highlights practical insights and ongoing conversations around modern development, making it relevant for engineers exploring real-world solutions.",

            'hackernews' => "{$prefix} a discussion titled \"{$title}\" is trending on Hacker News with a score of {$stats}. It’s actively debated by developers and often signals emerging ideas, tools, or shifts that could influence how engineers think about building and scaling systems.",

            default => "{$prefix} \"{$title}\" is currently attracting attention across the tech space."
        };

        $whyTrending = match ($source) {
            'github' => "Driven by continuous stars, forks, and developer contributions.",
            'devto' => "Strong engagement and relevance among developers.",
            'hackernews' => "Active discussion and upvotes from the tech community.",
            default => "Increasing visibility in the tech ecosystem."
        };

        $impact = "This could influence developers working in {$langText}, especially in how tools, workflows, and architectures evolve over time.";

        $techStack = !empty($item['tags'])
            ? implode(', ', array_slice($item['tags'], 0, 3))
            : $langText;

        return [
            'content'      => $content,
            'why_trending' => $whyTrending,
            'impact'       => $impact,
            'tech_stack'   => $techStack,
            'tags'         => $item['tags'] ?? [],
            'url'          => $item['url'] ?? null,
        ];
    }

    // ─────────────────────────────
    // SCORE
    // ─────────────────────────────
    private function calculateScore(array $item, int $maxStats, array $userTopics): float
    {
        $sourceWeight = match ($item['source']) {
            'github' => 1.0,
            'hackernews' => 0.9,
            'devto' => 0.8,
            default => 0.7,
        };

        $logStats = log($item['stats'] + 1);
        $maxLog   = log($maxStats + 1);

        $normalized = $maxLog > 0 ? ($logStats / $maxLog) : 0;
        $normalized *= $sourceWeight;

        $recency = 0.5;

        if (!empty($item['created_at'])) {
            try {
                $created = Carbon::parse($item['created_at']);
                $hours = max(now()->diffInHours($created), 1);
                $recency = 1 / ($hours + 2);
            } catch (\Exception $e) {
                $recency = 0.5;
            }
        }

        $boost = $this->personalizeBoost($item, $userTopics);

        return ($normalized * 0.5)
            + ($recency * 0.3)
            + ($boost * 0.2);
    }

    // ─────────────────────────────
    private function personalizeBoost(array $item, array $userTopics): float
    {
        if (empty($userTopics)) return 0;

        $text = strtolower(
            ($item['title'] ?? '') . ' ' .
            ($item['description'] ?? '') . ' ' .
            json_encode($item['tags'] ?? [])
        );

        $boost = 0;

        foreach ($userTopics as $topic) {

            $topic = strtolower($topic);

            if (in_array($topic, array_map('strtolower', $item['tags'] ?? []))) {
                $boost += 0.5;
                continue;
            }

            if (str_contains($text, $topic)) {
                $boost += 0.3;
            }

            foreach (explode(' ', $topic) as $word) {
                if (strlen($word) > 2 && str_contains($text, $word)) {
                    $boost += 0.1;
                }
            }
        }

        return min($boost, 0.8);
    }

    // ─────────────────────────────
    // 🔥 GITHUB FIXED TAGS
    // ─────────────────────────────
    private function fetchGitHub(): array
    {
        try {
            $res = Http::timeout(10)->get('https://api.github.com/search/repositories', [
                'q' => 'stars:>100',
                'sort' => 'stars',
                'per_page' => 10,
            ]);

            if (!$res->successful()) return [];

            return collect($res->json('items', []))->map(function ($r) {

                $desc = strtolower($r['description'] ?? '');

                $tags = array_filter([
                    strtolower($r['language'] ?? ''),
                    str_contains($desc, 'ai') ? 'ai' : null,
                    str_contains($desc, 'security') ? 'security' : null,
                    str_contains($desc, 'api') ? 'api' : null,
                ]);

                return [
                    'source' => 'github',
                    'title' => $r['full_name'] ?? '',
                    'description' => $r['description'] ?? '',
                    'author' => $r['owner']['login'] ?? '',
                    'stats' => (int) ($r['stargazers_count'] ?? 0),
                    'created_at' => $r['pushed_at'] ?? null,
                    'language' => $r['language'] ?? null,
                    'tags' => $tags,
                    'url' => $r['html_url'] ?? '',
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────
    private function fetchDevTo(): array
    {
        try {
            $res = Http::timeout(10)->get('https://dev.to/api/articles', [
                'per_page' => 10,
            ]);

            if (!$res->successful()) return [];

            return collect($res->json())->map(fn($a) => [
                'source' => 'devto',
                'title' => $a['title'] ?? '',
                'description' => $a['description'] ?? '',
                'author' => $a['user']['name'] ?? '',
                'stats' => (int) ($a['positive_reactions_count'] ?? 0),
                'created_at' => $a['published_at'] ?? null,
                'tags' => $a['tag_list'] ?? [],
                'url' => $a['url'] ?? '',
            ])->toArray();

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────
    private function fetchHackerNews(): array
    {
        try {
            $ids = Http::timeout(10)
                ->get('https://hacker-news.firebaseio.com/v0/topstories.json')
                ->json();

            if (!$ids) return [];

            return collect(array_slice($ids, 0, 10))->map(function ($id) {

                $item = Http::timeout(10)
                    ->get("https://hacker-news.firebaseio.com/v0/item/{$id}.json")
                    ->json();

                if (!$item) return null;

                return [
                    'source' => 'hackernews',
                    'title' => $item['title'] ?? '',
                    'description' => '',
                    'author' => $item['by'] ?? '',
                    'stats' => (int) ($item['score'] ?? 0),
                    'created_at' => isset($item['time'])
                        ? Carbon::createFromTimestamp($item['time'])
                        : null,
                    'tags' => ['tech'],
                    'url' => $item['url'] ?? "https://news.ycombinator.com/item?id={$id}",
                ];

            })->filter()->values()->toArray();

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────
    private function getUserTopics(): array
    {
        $user = Auth::user();
        if (!$user) return [];

        return $user->topics()
            ->pluck('name')
            ->map(fn($t) => strtolower($t))
            ->toArray();
    }
}
