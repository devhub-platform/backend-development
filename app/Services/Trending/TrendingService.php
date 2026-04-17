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
     * Return a paginated list of trending posts, optionally filtered by tag.
     *
     * Trending Score Formula:
     *   engagement = (reactions * 3) + (comments * 5) + SQRT(views * 1.5)
     *   decay      = EXP(-age_in_hours / 72)
     *   score      = engagement × decay
     *
     * - Reactions weight ×3: positive signal but easily inflated.
     * - Comments weight ×5: higher-effort engagement, stronger quality signal.
     * - Views: square-root scaled to dampen viral spikes.
     * - Decay over 72h: ensures older posts gradually leave trending.
     *
     * Results are cached per tag + page + per_page to prevent redundant queries.
     * Falls back to latest posts if no trending content is available.
     */
    public function getTrendingPosts(?int $tagId = null, int $perPage = 10): LengthAwarePaginator
    {
        $page     = request()->get('page', 1);
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
                 * Correlated subquery: reaction count per post.
                 * Avoids a heavy JOIN that would require GROUP BY on all selected columns.
                 */
                ->selectSub(function ($q) {
                    $q->from('reactions')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('reactions.reactable_id', 'posts.id')
                        ->where('reactions.reactable_type', Post::class);
                }, 'reactions_count')
                /**
                 * Correlated subquery: comment count per post.
                 * Excludes soft-deleted comments from the count.
                 */
                ->selectSub(function ($q) {
                    $q->from('comments')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('comments.post_id', 'posts.id')
                        ->whereNull('comments.deleted_at');
                }, 'comments_count')
                /**
                 * Trending score computed inline using correlated subqueries.
                 *
                 * COALESCE guards against NULL views on newly created posts.
                 * TIMESTAMPDIFF in hours provides precise time-based decay.
                 */
                ->selectRaw("
                    ROUND(
                        (
                            (COALESCE((
                                SELECT COUNT(*) FROM reactions
                                WHERE reactions.reactable_id = posts.id
                                  AND reactions.reactable_type = '" . addslashes(Post::class) . "'
                            ), 0) * 3)
                            +
                            (COALESCE((
                                SELECT COUNT(*) FROM comments
                                WHERE comments.post_id = posts.id
                                  AND comments.deleted_at IS NULL
                            ), 0) * 5)
                            +
                            (SQRT(COALESCE(posts.views, 0) * 1.5))
                        )
                        * EXP(-TIMESTAMPDIFF(HOUR, posts.created_at, NOW()) / 72)
                    , 4) AS trending_score
                ")
                ->where('posts.status', 'published')
                ->whereNull('posts.deleted_at')
                ->orderByDesc('trending_score')
                ->with([
                    // Select only safe fields — never expose password, tokens, or private data
                    'user:id,name,avatar_url',
                    'tags:id,name',
                ]);

            if ($tagId) {
                $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
            }

            $results = $query->paginate($perPage);

            /**
             * Graceful degradation:
             * If no posts have enough engagement to produce a meaningful score,
             * fall back to the most recently published posts.
             * This ensures the API never returns an empty response unnecessarily.
             */
            if ($results->isEmpty()) {
                return Post::query()
                    ->select([
                        'posts.id', 'posts.user_id', 'posts.title',
                        'posts.content', 'posts.status', 'posts.views',
                        'posts.created_at', 'posts.updated_at',
                    ])
                    ->selectRaw('0 AS reactions_count, 0 AS comments_count, 0 AS trending_score')
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
     * Return the top trending tags based on usage within the last 7 days.
     *
     * Only counts tags attached to published, non-deleted posts.
     * Cached for 20 minutes to reduce aggregation query frequency.
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
                // Limit to recent activity — tags trending 2 months ago are not relevant
                ->where('post_tags.created_at', '>=', now()->subDays(7))
                ->groupBy('tags.id', 'tags.name')
                ->orderByDesc('usage_count')
                ->limit($limit)
                ->get();
        });
    }
}
