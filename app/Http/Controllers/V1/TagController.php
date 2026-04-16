<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\TrendingPostResource;
use App\Http\Resources\TagResource;
use App\Models\Post;
use App\Models\Tag;
use App\Observers\TagObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController
{
    use AuthorizesRequests;

    public function popularTag(): JsonResponse
    {
        $tags = Tag::withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'message' => 'Popular tags retrieved successfully',
            'count' => $tags->count(),
            'data' => TagResource::collection($tags),
        ], 200);
    }

    public function allTags(): JsonResponse
    {
        $tags = Tag::withCount('posts')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'message' => 'All tags retrieved successfully',
            'count' => $tags->count(),
            'data' => TagResource::collection($tags),
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:tags,name',
        ]);

        $tag = Tag::create($validated);

        return response()->json([
            'message' => 'Tag created successfully',
            'data' => new TagResource($tag),
        ], 201);
    }

    public function attachTagsToPost(Request $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'tags' => 'required|array|max:10',
            'tags.*' => 'string|max:50',
        ]);

        $tagIds = collect($validated['tags'])
            ->unique()
            ->map(fn($tagName) => Tag::firstOrCreate(
                ['name' => $tagName],
                ['name' => trim($tagName)]
            )->id)
            ->values();

        $post->tags()->syncWithoutDetaching($tagIds);
        $post->load('tags');

        return response()->json([
            'message' => "Tags attached to post successfully",
            'data' => new TrendingPostResource($post),
        ], 200);
    }

    public function detachTagFromPost(Post $post, Tag $tag): JsonResponse
    {
        $this->authorize('update', $post);

        $post->tags()->detach($tag);
        $post->load('tags');

        return response()->json([
            'message' => "Tag detached from post successfully",
            'data' => new TrendingPostResource($post),
        ], 200);
    }
}
