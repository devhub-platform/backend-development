<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class TagController
{
    use AuthorizesRequests;
    public function popularTag()
    {
        $tags = Tag::withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'data' => $tags->map(function ($tag) {
                return [
                    'name' => $tag->name,
                    'posts_count' => $tag->posts_count,
                ];
            })
        ]);
    }

    public function allTags()
    {
        $tags = Tag::withCount('posts')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'Tags' => $tags->map(function ($tag) {
                return [
                    'name' => $tag->name,
                    'posts_count' => $tag->posts_count,
                ];
            })
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:tags,name',
        ]);

        $tag = Tag::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Tag created successfully',
            'tag' => new TagResource($tag),
        ], 201);
    }
}
