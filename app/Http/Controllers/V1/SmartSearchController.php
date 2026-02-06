<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\CommentResource;
use App\Http\Resources\SearchPostResource;
use App\Http\Resources\SearchTagsResource;
use App\Http\Resources\SearchUsersResource;
use App\Models\Post;
use App\Models\SearchHistory;
use App\Services\SemanticSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SmartSearchController
{
    protected SemanticSearchService $semanticSearch;

    public function __construct(SemanticSearchService $semanticSearch)
    {
        $this->semanticSearch = $semanticSearch;
    }

    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:500',
            'types' => 'sometimes|array',
            'types.*' => 'in:posts,users,tags,comments',
            'limit' => 'sometimes|integer|min:1|max:50',
            'threshold' => 'sometimes|numeric|min:0|max:1',
            'model' => 'sometimes|string|in:' . implode(',', array_keys($this->semanticSearch->getAvailableModels())),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = $request->input('query');
        $types = $request->input('types', ['posts', 'users', 'tags']);
        $limit = $request->input('limit', 10);
        $threshold = $request->input('threshold', 0.5);
        $model = $request->input('model');

        try {
            // Store search in history
            $this->storeSearchHistory($query);

            $results = $this->semanticSearch->search($query, [
                'types' => $types,
                'limit' => $limit,
                'threshold' => $threshold,
                'model' => $model,
            ]);

            // Format results
            $formattedResults = $this->formatResults($results);

            // Calculate total count
            $totalCount = collect($formattedResults)->sum(fn($items) => count($items));

            return response()->json([
                'message' => 'Smart search completed successfully',
                'query' => $query,
                'total_results' => $totalCount,
                'results' => $formattedResults,
                'meta' => [
                    'types_searched' => $types,
                    'threshold' => $threshold,
                    'limit_per_type' => $limit,
                    'model_used' => $model ?? config('services.openrouter.embedding_model', 'openai/text-embedding-3-small'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Smart search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Search failed. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search only posts with semantic understanding
     */
    public function searchPosts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:500',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = $request->input('query');
        $limit = $request->input('limit', 10);

        try {
            $this->storeSearchHistory($query);

            $results = $this->semanticSearch->search($query, [
                'types' => ['posts'],
                'limit' => $limit,
            ]);

            $posts = $results['posts'] ?? collect([]);

            // Load relations if posts exist
            if ($posts->isNotEmpty()) {
                $postIds = $posts->pluck('id')->toArray();
                $scores = $posts->pluck('similarity_score', 'id')->toArray();

                $posts = Post::whereIn('id', $postIds)
                    ->with(['user', 'tags'])
                    ->get()
                    ->map(function ($post) use ($scores) {
                        $post->similarity_score = $scores[$post->id] ?? null;
                        return $post;
                    })
                    ->sortByDesc('similarity_score')
                    ->values();
            }

            return response()->json([
                'message' => 'Posts found successfully',
                'query' => $query,
                'count' => $posts->count(),
                'data' => SearchPostResource::collection($posts),
            ]);

        } catch (\Exception $e) {
            Log::error('Smart post search failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function findSimilarPosts(Request $request, Post $post): JsonResponse
    {
        $limit = $request->input('limit', 5);

        try {
            $similar = $this->semanticSearch->findSimilar($post, $limit, ['user', 'tags']);

            return response()->json([
                'message' => 'Similar posts found',
                'post_id' => $post->id,
                'count' => $similar->count(),
                'data' => SearchPostResource::collection($similar),
            ]);

        } catch (\Exception $e) {
            Log::error('Find similar posts failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to find similar posts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function suggestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:1|max:100',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = $request->input('query');
        $limit = $request->input('limit', 5);

        try {
            $suggestions = $this->semanticSearch->getSuggestions($query, $limit);

            // Also get trending/popular searches
            $trending = $this->getTrendingSearches($limit);

            return response()->json([
                'message' => 'Suggestions retrieved',
                'suggestions' => $suggestions,
                'trending' => $trending,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get suggestions',
                'suggestions' => [],
                'trending' => [],
            ]);
        }
    }

    /**
     * Get embedding statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->semanticSearch->getStats();

            return response()->json([
                'message' => 'Embedding statistics',
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available embedding models
     */
    public function models(): JsonResponse
    {
        $models = $this->semanticSearch->getAvailableModels();
        $currentModel = config('services.openrouter.embedding_model', 'openai/text-embedding-3-small');

        return response()->json([
            'message' => 'Available embedding models',
            'current_model' => $currentModel,
            'models' => collect($models)->map(function ($info, $id) use ($currentModel) {
                return [
                    'id' => $id,
                    'name' => $info['name'],
                    'dimension' => $info['dimension'],
                    'best_for' => $info['best_for'],
                    'is_current' => $id === $currentModel,
                ];
            })->values(),
        ]);
    }

    protected function formatResults(array $results): array
    {
        $formatted = [];

        if (isset($results['posts'])) {
            $posts = $results['posts']->load('user', 'tags');
            $formatted['posts'] = $posts->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => str($post->content)->limit(150)->toString(),
                    'cover_image' => $post->cover_image,
                    'read_time' => $post->read_time,
                    'similarity_score' => $post->similarity_score ?? null,
                    'author' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                        'username' => $post->user->username,
                        'avatar_url' => $post->user->avatar_url,
                    ],
                    'tags' => $post->tags->pluck('name'),
                    'created_at' => $post->created_at->diffForHumans(),
                ];
            })->values();
        }

        if (isset($results['users'])) {
            $formatted['users'] = $results['users']->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar_url' => $user->avatar_url,
                    'bio' => str($user->bio)->limit(100)->toString(),
                    'skills' => $user->skills ?? [],
                    'similarity_score' => $user->similarity_score ?? null,
                ];
            })->values();
        }

        if (isset($results['tags'])) {
            $formatted['tags'] = $results['tags']->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug ?? null,
                    'description' => $tag->description,
                    'posts_count' => $tag->posts()->count(),
                    'similarity_score' => $tag->similarity_score ?? null,
                ];
            })->values();
        }

        if (isset($results['comments'])) {
            $formatted['comments'] = $results['comments']->load('user', 'post')->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'body' => str($comment->body)->limit(150)->toString(),
                    'similarity_score' => $comment->similarity_score ?? null,
                    'author' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'username' => $comment->user->username,
                    ],
                    'post' => [
                        'id' => $comment->post->id,
                        'title' => $comment->post->title,
                        'slug' => $comment->post->slug,
                    ],
                    'created_at' => $comment->created_at->diffForHumans(),
                ];
            })->values();
        }

        return $formatted;
    }

    /**
     * Store search query in history
     */
    protected function storeSearchHistory(string $query): void
    {
        $userId = auth()->id();
        if (!$userId) return;

        try {
            SearchHistory::updateOrCreate(
                ['user_id' => $userId, 'query' => trim($query)],
                ['updated_at' => now()]
            );

            // Update global trending searches
            $trending = Cache::get('trending_searches', []);
            $trending[$query] = ($trending[$query] ?? 0) + 1;
            Cache::put('trending_searches', $trending, now()->addDay());

        } catch (\Exception $e) {
            Log::warning('Failed to store search history', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get trending searches
     */
    protected function getTrendingSearches(int $limit = 5): array
    {
        $trending = Cache::get('trending_searches', []);
        arsort($trending);

        return array_slice(array_keys($trending), 0, $limit);
    }
}

