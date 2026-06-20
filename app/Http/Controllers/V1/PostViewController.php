<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\RecentViewsResource;
use App\Services\ViewedPostService;

class PostViewController
{
    private ViewedPostService $viewedPostService;

    public function __construct(ViewedPostService $viewedPostService)
    {
        $this->viewedPostService = $viewedPostService;
    }


    public function getRecentViewedPosts()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $viewedPosts = $this->viewedPostService->getUserViewedPosts($user->id, 15);

        return response()->json([
            'viewed_posts_count' => count($viewedPosts),
            'data' => RecentViewsResource::collection($viewedPosts),
        ]);
    }

    public function clearViewedPosts() // clear all viewed posts for the authenticated user
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $this->viewedPostService->clearUserViewHistory($user->id);

        return response()->json([
            'message' => 'Viewed posts cleared successfully.',
        ]);
    }

    public function getUserViewCount()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $viewCount = $this->viewedPostService->getUserViewCount($user->id);

        if (!$viewCount) {
            return response()->json([
                'data' => [
                    'total_posts_viewed' => 0,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'total_posts_viewed' => $viewCount,
            ],
        ]);
    }

}
