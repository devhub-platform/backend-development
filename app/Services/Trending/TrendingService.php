<?php

namespace App\Services\Trending;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TrendingService
{
    /**
     * Get paginated trending posts.
     *
     * If a tag is provided, results are filtered by that tag.
     * Trending is calculated using reactions, comments, views, and recency decay.
     *
     * Results are cached per page and per tag for performance optimization.
     */
    public function getTrendingPosts(?int $tagId = null, int $perPage = 10): LengthAwarePaginator
    {
        $page = request()->get('page', 1);

        // Cache key includes tag + pagination to avoid cache collisions
        $cacheKey = "trending:posts:{$tagId}:page:{$page}:per:{$perPage}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($tagId, $perPage) {

            $query = Post::query()
                ->select([
                    'posts.id',
                    'posts.user_id',
                    'posts.title',
                    'posts.content',
                    'posts.status',
                    'posts.views',
                    'posts.created_at',
                    'posts.updated_at',
                ])

                /**
                 * Subquery: count reactions per post
                 * Used as a component of the trending score
                 */
                ->selectSub(function ($q) {
                    $q->from('reactions')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('reactions.reactable_id', 'posts.id')
                        ->where('reactions.reactable_type', Post::class);
                }, 'reactions_count')

                /**
                 * Subquery: count active comments per post
                 * Excludes soft-deleted comments
                 */
                ->selectSub(function ($q) {
                    $q->from('comments')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('comments.post_id', 'posts.id')
                        ->whereNull('comments.deleted_at');
                }, 'comments_count')

                /**
                 * Trending Score Formula:
                 *
                 * - Reactions weight: x3
                 * - Comments weight: x5 (higher engagement value)
                 * - Views: sqrt scaling to reduce impact of viral spikes
                 * - Time decay: exponential decay over 72 hours
                 *
                 * Final score prioritizes recent and highly engaging content
                 */
                ->selectRaw("
                    (
                        (COALESCE((SELECT COUNT(*) FROM reactions
                            WHERE reactions.reactable_id = posts.id
                            AND reactions.reactable_type = '" . Post::class . "'), 0) * 3)
                        +
                        (COALESCE((SELECT COUNT(*) FROM comments
                            WHERE comments.post_id = posts.id
                            AND comments.deleted_at IS NULL), 0) * 5)
                        +
                        (SQRT(COALESCE(posts.views, 0)) * 1.5)
                    )
                    * EXP(-TIMESTAMPDIFF(HOUR, posts.created_at, NOW()) / 72)
                    AS trending_score
                ")

                // Only published and non-deleted posts are eligible for trending
                ->where('posts.status', 'published')
                ->whereNull('posts.deleted_at')

                // Order by computed trending score descending
                ->orderByDesc('trending_score')

                // Eager load relationships to prevent N+1 queries
                ->with([
                    'user:id,name,avatar_url',
                    'tags:id,name',
                ]);

            // Optional filtering by tag
            if ($tagId) {
                $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
            }

            $results = $query->paginate($perPage);

            /**
             * Fallback strategy:
             * If no trending results exist, return latest published posts
             * without scoring (safe degradation)
             */
            if ($results->isEmpty()) {
                return Post::query()
                    ->select([
                        'posts.id',
                        'posts.user_id',
                        'posts.title',
                        'posts.content',
                        'posts.status',
                        'posts.views',
                        'posts.created_at',
                        'posts.updated_at',
                    ])
                    ->selectRaw('0 as reactions_count, 0 as comments_count, 0 as trending_score')
                    ->where('status', 'published')
                    ->whereNull('deleted_at')
                    ->orderByDesc('created_at')
                    ->with(['user:id,name,avatar_url', 'tags:id,name'])
                    ->paginate($perPage);
            }

            return $results;
        });
    }

    /**
     * Get trending tags based on recent usage (last 7 days).
     *
     * Counts tag usage across published posts only.
     * Cached to reduce heavy aggregation queries.
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

                // Only consider recent tag usage (last 7 days)
                ->where('post_tags.created_at', '>=', now()->subDays(7))

                ->groupBy('tags.id', 'tags.name')
                ->orderByDesc('usage_count')
                ->limit($limit)
                ->get();
        });
    }
}
