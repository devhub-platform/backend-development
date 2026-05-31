<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trending\TrendingPostRequest;
use App\Http\Resources\TrendingPostResource;
use App\Services\Trending\TrendingService;
use App\Services\Trending\TechTrendService;
use Illuminate\Http\JsonResponse;

class TrendingController extends Controller
{
    public function __construct(
        private TrendingService  $trendingService,
        private TechTrendService $techTrendService,
    ) {}

    // ─── Posts ────────────────────────────────────────────────────────────────

    public function posts(TrendingPostRequest $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 10), 50);
        $page    = $request->integer('page', 1);

        $paginator = $this->trendingService->getTrendingPosts(
            tagId:   $request->integer('tag_id') ?: null,
            perPage: $perPage,
            page:    $page,
        );

        return response()->json([
            'success' => true,
            'data'    => TrendingPostResource::collection($paginator->items()),
            'meta'    => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    // ─── Tags ─────────────────────────────────────────────────────────────────

    public function tags(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->trendingService->getTrendingTags(),
        ]);
    }

    // ─── Tech Trends Feed (lightweight, no AI) ────────────────────────────────

    public function tech(): JsonResponse
    {
        $trends = $this->techTrendService->getTechTrends();

        return response()->json([
            'success' => true,
            'data'    => $trends,
            'total'   => count($trends),
        ]);
    }

    // ─── Tech Trend Detail (with AI enrichment) ───────────────────────────────

    /**
     * GET /tech-trends/{id}
     *
     * Returns full item with AI fields.
     * - If AI is cached → instant response
     * - If not cached → returns fallback + triggers background enrichment via defer()
     *   Next request will return the real AI data
     */
    public function techDetail(string $id): JsonResponse
    {
        $item = $this->techTrendService->getTrendById($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Trend not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $item,
        ]);
    }
}
