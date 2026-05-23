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
use App\Services\InteractionLoggerService;
use App\Services\SearchService;
use Illuminate\Support\Facades\Http;

class SearchController
{
    private SearchService $searchService;
    private InteractionLoggerService $interactionLoggerService;

    public function __construct(SearchService $searchService, InteractionLoggerService $interactionLoggerService)
    {
        $this->searchService = $searchService;
        $this->interactionLoggerService = $interactionLoggerService;
    }

    public function searchPosts(Request $request, Post $post): JsonResponse
    {
        $data = $this->searchService->searchPosts($request, $post);

//        if (auth()->check()) {
//            $searchQuery = $request->input('q', '');
//            $this->interactionLoggerService->logInteraction(
//                userId: auth()->id(),
//                category: 'Search',
//                action: 'search',
//                duration: 0,
//                additionalData: ['search_term' => $searchQuery]
//            );
//        }
        $searchQuery = $request->input('q', '');

        Http::post('https://memo1714-devhub-ai-api.hf.space/log_interaction', [
            'user_id' => auth()->id(),
            'category' => $searchQuery,
            'action' => 'Search',
            'duration' => 50,
        ]);

        return response()->json([
            'message' => 'Posts found successfully',
            'results_found' => $data['count'],
            'data' => SearchPostResource::collection($data['results']),
        ]);
    }

    public function searchUsersByUsername(Request $request, User $user): JsonResponse
    {
        $data = $this->searchService->searchUsers($request, $user);

        return response()->json([
            'message' => 'Users found successfully',
            'results_found' => $data['count'],
            'data' => SearchUsersResource::collection($data['results']),
        ]);
    }

    public function searchTags(Request $request, Tag $tag): JsonResponse
    {
        $data = $this->searchService->searchTags($request, $tag);

        Http::post('https://memo1714-devhub-ai-api.hf.space/log_interaction', [
            'user_id' => auth()->id(),
            'category' => $request->input('tag', 'Article'),
            'action' => 'Search',
            'duration' => 50,
        ]);

        return response()->json([
            'message' => 'Tags found successfully',
            'results_found' => $data['count'],
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
