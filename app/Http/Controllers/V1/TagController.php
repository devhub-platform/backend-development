<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\PostResource;
use App\Http\Resources\TagResource;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        Log::info('New tag created: ' . $tag->name . ' by user ID: ' . auth()->id());
        return response()->json([
            'message' => 'Tag created successfully',
            'tag' => new TagResource($tag),
        ], 201);
    }

    public function attachTagsToPost(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $request->validate(['tags' => 'required|array']);

        $tags = collect($request->tags)->map(function ($tagName) {
            return Tag::firstOrCreate(['name' => $tagName])->id;
        });

        $post->tags()->syncWithoutDetaching($tags);
        $post->load('tags');
        $post->tags->each->setHidden(['pivot']);

        return response()->json([
            'message' => "Tags attached to Post {$post->title} successfully",
            'data' => new PostResource($post)
        ], 200);
    }

    public function detachTagFromPost(Post $post, Tag $tag)
    {
        $post->tags()->detach($tag);
        return response()->json([
            'message' => "Tag {$tag->name} detached from Post {$post->title} successfully",
            $post->load('tags')
        ]);
    }
}
