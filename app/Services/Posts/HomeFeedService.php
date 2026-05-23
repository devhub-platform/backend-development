<?php

namespace App\Services\Posts;

use App\Models\Post;
use App\Models\User;
use App\Services\AI\EmbeddingService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HomeFeedService
{
    public function __construct(private EmbeddingService $embedding)
    {
    }

    public function build(?User $user, int $perPage = 10, int $page = 1, ?string $path = null, array $query = []): LengthAwarePaginator
    {
        $path ??= request()->url();
        $query = $query ?: request()->query();

        if (!$user) {
            return Post::query()
                ->with(['user', 'tags'])
                ->where('status', '!=', 'draft')
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);
        }

        $blockedUserIds = $this->blockedUserIds($user);
        $followingIds = $user->following()->pluck('users.id')->filter()->unique()->values()->all();
        $followedTagNames = $user->followedTags()->pluck('name')->filter()->unique()->values()->all();
        $interestTerms = $this->selectedInterests($user, $followedTagNames);
        $interestVector = $this->interestVector($interestTerms);

        $candidates = $this->candidatePool($blockedUserIds, $followingIds, $followedTagNames);

        if ($candidates->isEmpty()) {
            return Post::query()
                ->with(['user', 'tags'])
                ->where('status', '!=', 'draft')
                ->prioritizeFollowedTags($user)
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);
        }

        $scored = $candidates->map(function (Post $post) use ($interestVector, $interestTerms, $followingIds, $followedTagNames) {
            return [
                'post' => $post,
                'score' => $this->scorePost($post, $interestVector, $interestTerms, $followingIds, $followedTagNames),
            ];
        });

        $sorted = $scored->sort(function (array $left, array $right) {
            if ($left['score'] === $right['score']) {
                return ($right['post']->created_at?->timestamp ?? 0) <=> ($left['post']->created_at?->timestamp ?? 0);
            }

            return $right['score'] <=> $left['score'];
        })->values()->pluck('post');

        $total = $sorted->count();
        $items = $sorted->slice(($page - 1) * $perPage, $perPage)->values()->all();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $path,
            'query' => $query,
        ]);
    }

    private function selectedInterests(User $user, array $followedTagNames): array
    {
        $topics = $user->topics()
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return !empty($topics) ? $topics : $followedTagNames;
    }

    private function interestVector(array $terms): array
    {
        if (empty($terms)) {
            return [];
        }

        $cacheKey = 'home:interest-vector:' . md5(json_encode(array_values($terms)));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($terms) {
            return $this->embedding->embed(implode(' | ', $terms));
        });
    }

    private function candidatePool(array $blockedUserIds, array $followingIds, array $followedTagNames): Collection
    {
        $recentPosts = Post::query()
            ->with(['user', 'tags'])
            ->where('status', '!=', 'draft')
            ->whereNotIn('user_id', $blockedUserIds)
            ->latest()
            ->limit(120)
            ->get();

        $followingPosts = empty($followingIds)
            ? collect()
            : Post::query()
                ->with(['user', 'tags'])
                ->where('status', '!=', 'draft')
                ->whereNotIn('user_id', $blockedUserIds)
                ->whereIn('user_id', $followingIds)
                ->latest()
                ->limit(120)
                ->get();

        $tagPosts = empty($followedTagNames)
            ? collect()
            : Post::query()
                ->with(['user', 'tags'])
                ->where('status', '!=', 'draft')
                ->whereNotIn('user_id', $blockedUserIds)
                ->whereHas('tags', fn ($query) => $query->whereIn('name', $followedTagNames))
                ->latest()
                ->limit(120)
                ->get();

        return $recentPosts
            ->merge($followingPosts)
            ->merge($tagPosts)
            ->unique('id')
            ->values();
    }

    private function scorePost(Post $post, array $interestVector, array $interestTerms, array $followingIds, array $followedTagNames): float
    {
        $score = 0.0;
        $postVector = $this->embedding->getCachedEmbedding($post);

        if (!empty($interestVector) && !empty($postVector)) {
            $score += $this->embedding->cosine($interestVector, $postVector) * 100;
        } else {
            $score += $this->keywordScore($post, $interestTerms);
        }

        if (in_array($post->user_id, $followingIds, true)) {
            $score += 15;
        }

        $tagMatches = $post->tags->pluck('name')->intersect($followedTagNames)->count();
        if ($tagMatches > 0) {
            $score += min(15, $tagMatches * 5);
        }

        $daysOld = max(1, now()->diffInDays($post->created_at));
        $score += max(0, 10 - min(10, $daysOld / 3));

        return $score;
    }

    private function keywordScore(Post $post, array $interestTerms): float
    {
        if (empty($interestTerms)) {
            return 0.0;
        }

        $text = mb_strtolower($post->title . ' ' . ($post->content ?? '') . ' ' . $post->tags->pluck('name')->implode(' '));
        $score = 0.0;

        foreach ($interestTerms as $term) {
            $term = mb_strtolower(trim((string) $term));
            if ($term === '') {
                continue;
            }

            if (str_contains($text, $term)) {
                $score += 12;
                continue;
            }

            foreach (preg_split('/\s+/', $term) ?: [] as $word) {
                if (mb_strlen($word) > 2 && str_contains($text, $word)) {
                    $score += 3;
                }
            }
        }

        return $score;
    }

    private function blockedUserIds(User $user): array
    {
        return $user->blockedUsers()->pluck('users.id')
            ->merge($user->blockers()->pluck('users.id'))
            ->unique()
            ->values()
            ->all();
    }
}
