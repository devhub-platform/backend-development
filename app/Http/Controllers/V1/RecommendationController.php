<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class RecommendationController
{
    public function __construct(private RecommendationService $recommendationService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $rawInterests = $request->input('interests', []);
        $interests = [];
        if (is_array($rawInterests)) {
            $interests = $rawInterests;
        } elseif (is_string($rawInterests) && trim($rawInterests) !== '') {
            $interests = array_map('trim', explode(',', $rawInterests));
        }

        $remote = $this->recommendationService->fetchRemoteRecommendations($user?->id, $interests);
        $categories = $this->recommendationService->topCategoriesFromResponse($remote, 4);

        if (!empty($categories)) {
            // Find posts that have tags matching the top categories
            $posts = Post::with(['user', 'tags'])
                ->where('status', '!=', 'draft')
                ->whereHas('tags', function ($q) use ($categories) {
                    $q->whereIn('name', $categories);
                })
                ->latest()
                ->limit(12)
                ->get();
        } else {
            // fallback: popular recent posts
            $posts = Post::with(['user', 'tags'])
                ->where('status', '!=', 'draft')
                ->orderByDesc('views')
                ->limit(12)
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'remote' => $remote,
            'data' => PostResource::collection($posts),
        ]);
    }
}

