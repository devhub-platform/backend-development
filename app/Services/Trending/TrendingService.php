<?php

namespace App\Services\Trending;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TrendingService
{
    /**
     * Get trending posts.
     *
     * Algorithm:
     * engagement  = (reactions * 3) + (comments * 5) + SQRT(views * 1.5)
     * decay       = EXP(-age_in_hours / 72)
     * score       = engagement * decay
     */
    public function getTrendingPosts(?int $tagId = null, int $perPage = 10): LengthAwarePaginator
    {
        $cacheKey = sprintf(
            'trending:posts:%s:page:%d',
            $tagId ?? 'global',
            request()->get('page', 1)
        );

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($tagId, $perPage) {
            $query = Post::query()
                ->select('posts.*')
                ->selectRaw("
                    ROUND(
                        (
                            (COALESCE(COUNT(DISTINCT r.id), 0) * 3) +
                            (COALESCE(COUNT(DISTINCT c.id), 0) * 5) +
                            (SQRT(GREATEST(posts.views, 0)) * 1.5)
                        )
                        * EXP(-TIMESTAMPDIFF(HOUR, posts.created_at, NOW()) / 72)
                    , 4) AS trending_score
                ")
                ->leftJoin('reactions as r', function ($join) {
                    $join->on('r.reactable_id', '=', 'posts.id')
                        ->where('r.reactable_type', '=', Post::class);
                })
                ->leftJoin('comments as c', 'c.post_id', '=', 'posts.id')
                ->where('posts.status', 'published')
                ->whereNull('posts.deleted_at')
                ->groupBy('posts.id')
                ->havingRaw('trending_score > 0')
                ->orderByDesc('trending_score')
                ->with(['user', 'tags']);

            if ($tagId) {
                $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
            }

            return $query->paginate($perPage);
        });
    }

    /**
     * Get trending tags based on post usage in the last 7 days.
     */
    public function getTrendingTags(int $limit = 10): Collection
    {
        return Cache::remember('trending:tags', now()->addMinutes(20), function () use ($limit) {
            return Tag::select('tags.id', 'tags.name')
                ->selectRaw('COUNT(post_tags.post_id) as usage_count')
                ->join('post_tags', 'post_tags.tag_id', '=', 'tags.id')
                ->join('posts', 'posts.id', '=', 'post_tags.post_id')
                ->where('posts.status', 'published')
                ->whereNull('posts.deleted_at')
                ->where('post_tags.created_at', '>=', now()->subDays(7))
                ->groupBy('tags.id', 'tags.name')
                ->orderByDesc('usage_count')
                ->limit($limit)
                ->get();
        });
    }
}
