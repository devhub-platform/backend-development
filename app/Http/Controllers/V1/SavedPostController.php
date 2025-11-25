<?php

namespace App\Http\Controllers\V1;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedPostController
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $posts = $user->savedPosts()
            ->with(['user', 'tags'])
            ->latest('created_at')
            ->get();
        if ($posts->isEmpty()) {
            return response()->json(['message' => 'No saved posts found'], 404);
        }

        return response()->json(['data' => $posts]);
    }

    public function store(Post $post): JsonResponse
    {
        $user = auth()->user();

        $user->savedPosts()->syncWithoutDetaching($post->id);

        return response()->json(['message' => 'Post saved for later', 'post' => $post], 201);
    }

    public function destroy(Post $post): JsonResponse
    {
        $user = auth()->user();

        $user->savedPosts()->detach($post->id);

        return response()->json(['message' => 'Post removed from saved list']);
    }
}
