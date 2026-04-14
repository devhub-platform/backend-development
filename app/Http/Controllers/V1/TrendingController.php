<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trending\TrendingPostRequest;
use App\Services\Trending\TechTrendService;
use App\Services\Trending\TrendingService;
use Illuminate\Http\JsonResponse;

class TrendingController extends Controller
{
    public function __construct(
        private TrendingService  $trendingService,
        private TechTrendService $techTrendService,
    ) {}

    /**
     * GET /api/v1/trending/posts?tag_id={id}&per_page={n}
     */
    public function posts(TrendingPostRequest $request): JsonResponse
    {
        $posts = $this->trendingService->getTrendingPosts(
            tagId:   $request->integer('tag_id') ?: null,
            perPage: $request->integer('per_page', 10),
        );

        return response()->json([
            'success' => true,
            'data'    => $posts->items(),
            'meta'    => [
                'total'        => $posts->total(),
                'per_page'     => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/trending/tags
     */
    public function tags(): JsonResponse
    {
        $tags = $this->trendingService->getTrendingTags();

        return response()->json([
            'success' => true,
            'data'    => $tags,
        ]);
    }

    /**
     * GET /api/v1/trending/tech
     */
    public function tech(): JsonResponse
    {
        $trends = $this->techTrendService->getTechTrends();

        return response()->json([
            'success' => true,
            'data'    => $trends,
            'total'   => count($trends),
        ]);
    }
}
