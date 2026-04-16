<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trending\TrendingPostRequest;
use App\Http\Resources\TrendingPostResource;
use App\Services\Trending\TechTrendService;
use App\Services\Trending\TrendingService;
use Illuminate\Http\JsonResponse;

class TrendingController extends Controller
{
    public function __construct(
        private TrendingService  $trendingService,
        private TechTrendService $techTrendService,
    ) {}

    public function posts(TrendingPostRequest $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 10), 50);

        $posts = $this->trendingService->getTrendingPosts(
            tagId:   $request->integer('tag_id') ?: null,
            perPage: $perPage,
        );

        return response()->json([
            'success' => true,
            'data'    => TrendingPostResource::collection($posts),
            'meta'    => [
                'total'        => $posts->total(),
                'per_page'     => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
            ],
        ]);
    }

    public function tags(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->trendingService->getTrendingTags(),
        ]);
    }

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
