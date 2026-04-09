<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\TopicRequest;
use App\Http\Resources\TopicResource;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TopicController
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Topic::class);

        $topics = Topic::where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'message' => 'Topics retrieved successfully',
            'count' => $topics->count(),
            'data' => TopicResource::collection($topics),
        ], 200);
    }

    public function store(TopicRequest $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validated();
        $this->authorize('create', Topic::class);

        $topic = Topic::create(array_merge([
            'display_order' => 0,
            'is_active' => true,
        ], $validated));

        return response()->json([
            'message' => 'Topic created successfully',
            'data' => new TopicResource($topic),
        ], 201);
    }

    public function update(TopicRequest $request, Topic $topic): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validated();
        $this->authorize('update', $topic);

        $topic->update($validated);

        return response()->json([
            'message' => 'Topic updated successfully',
            'data' => new TopicResource($topic),
        ], 200);
    }

    public function destroy(Topic $topic): JsonResponse
    {
        $user = Auth::user();

        $this->authorize('delete', $topic);

        $topic->delete();

        return response()->json([
            'message' => 'Topic deleted successfully',
        ], 200);
    }


    public function show($topicId): JsonResponse
    {
        $topic = Topic::where('is_active', true)->find($topicId);

        if (!$topic) {
            return response()->json([
                'message' => 'Topic not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Topic retrieved successfully',
            'data' => new TopicResource($topic),
        ], 200);
    }

    /**
     * Get user's selected topics
     * Only accessible to authenticated users
     */
    public function getUserTopics(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $topics = $user->topics()->where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->get();

        return response()->json([
            'message' => 'User topics retrieved successfully',
            'count' => $topics->count(),
            'data' => $topics,
        ], 200);
    }

    /**
     * Add topics to user's existing selection
     */
    public function addTopics(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'topic_ids' => 'required|array|min:1',
            'topic_ids.*' => 'integer|exists:topics,id',
        ]);

        // Verify all topics exist and are active
        $topics = Topic::where('is_active', true)
            ->whereIn('id', $validated['topic_ids'])
            ->pluck('id')
            ->toArray();

        if (count($topics) !== count($validated['topic_ids'])) {
            return response()->json([
                'message' => 'One or more selected topics are invalid or inactive',
            ], 422);
        }

        // Attach topics (add to existing)
        $user->topics()->syncWithoutDetaching($topics);

        return response()->json([
            'message' => 'Topics added successfully',
            'data' => $user->topics()->get(),
        ], 200);
    }

    /**
     * Remove specific topics from user's selection
     */
    public function removeTopics(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'topic_ids' => 'required|array|min:1',
            'topic_ids.*' => 'integer|exists:topics,id',
        ]);

        // Detach topics
        $user->topics()->detach($validated['topic_ids']);

        return response()->json([
            'message' => 'Topics removed successfully',
            'data' => $user->topics()->get(),
        ], 200);
    }

    /**
     * Clear all topics from user's selection
     */
    public function clearTopics(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $user->topics()->detach();

        return response()->json([
            'message' => 'All topics cleared successfully',
        ], 200);
    }
}

