<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\SearchPostResource;
use App\Http\Resources\SearchTagsResource;
use App\Http\Resources\SearchUsersResource;
use App\Models\Post;
use App\Models\SearchHistory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\SearchService;

class SearchController
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function searchPosts(Request $request, Post $post): JsonResponse
    {
        $data = $this->searchService->searchPosts($request, $post);

        return response()->json([
            'message' => 'Posts found successfully',
            'count' => $data['count'],
            'data' => SearchPostResource::collection($data['results']),
        ]);
    }

    public function searchUsersByUsername(Request $request, User $user): JsonResponse
    {
        $data = $this->searchService->searchUsers($request, $user);

        return response()->json([
            'message' => 'Users found successfully',
            'count' => $data['count'],
            'data' => SearchUsersResource::collection($data['results']),
        ]);
    }

    public function searchTags(Request $request, Tag $tag): JsonResponse
    {
        $data = $this->searchService->searchTags($request, $tag);

        return response()->json([
            'message' => 'Tags found successfully',
            'count' => $data['count'],
            'data' => SearchTagsResource::collection($data['results']),
        ]);
    }

    public function getSearchHistory(): JsonResponse
    {
        return response()->json([
            'data' => $this->searchService->getSearchHistory(),
        ], 200
        );
    }

    public function clearSearchHistory(): JsonResponse
    {
        $this->searchService->clearSearchHistory();

        return response()->json([
            'message' => 'Search history cleared successfully',
        ]);
    }
}
