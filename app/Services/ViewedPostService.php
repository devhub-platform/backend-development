<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostView;
use App\Models\User;
use Illuminate\Pagination\Paginator;

class ViewedPostService
{
    /**
     * Track a post view for a user
     *
     * @param int $userId
     * @param int $postId
     * @return PostView
     */
    public function trackView(int $userId, int $postId): PostView
    {
        return PostView::updateOrCreate(
            ['user_id' => $userId, 'post_id' => $postId],
            ['viewed_at' => now()]
        );
    }

    /**
     * Get all viewed posts for a user with pagination
     *
     * @param int $userId
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserViewedPosts(int $userId, int $perPage = 15)
    {
        return PostView::where('user_id', $userId)
            ->with('post.user', 'post.tags')
            ->orderBy('viewed_at', 'desc')
            ->paginate($perPage);
    }


    public function isPostViewed(int $userId, int $postId): bool
    {
        return PostView::where('user_id', $userId)
            ->where('post_id', $postId)
            ->exists();
    }

    /**
     * Get the count of views for a post
     *
     * @param int $postId
     * @return int
     */
    public function getPostViewCount(int $postId): int
    {
        return PostView::where('post_id', $postId)->count();
    }

    /**
     * Get the count of posts viewed by a user
     *
     * @param int $userId
     * @return int
     */
    public function getUserViewCount(int $userId): int
    {
        return PostView::where('user_id', $userId)->count();
    }

    /**
     * Clear all viewed posts history for a user
     *
     * @param int $userId
     * @return bool
     */
    public function clearUserViewHistory(int $userId): bool
    {
        PostView::where('user_id', $userId)->delete();
        return true;
    }

    /**
     * Get viewing statistics for a post
     *
     * @param int $postId
     * @return array
     */
    public function getPostViewStats(int $postId): array
    {
        $views = PostView::where('post_id', $postId)->get();

        return [
            'total_views' => $views->count(),
            'unique_viewers' => $views->groupBy('user_id')->count(),
            'last_viewed_at' => $views->max('viewed_at'),
            'first_viewed_at' => $views->min('viewed_at'),
        ];
    }

    public function getRecentViewedPosts(int $userId, int $limit = 10)
    {
        return PostView::where('user_id', $userId)
            ->with('post.user', 'post.tags')
            ->orderBy('viewed_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
