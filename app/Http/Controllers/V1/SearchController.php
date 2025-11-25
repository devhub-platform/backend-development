<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\SearchPostResource;
use App\Http\Resources\SearchTagsResource;
use App\Http\Resources\SearchUsersResource;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchController
{
    public function searchPosts(Request $request, Post $post)
    {
        $query = $request->input('post');
        $results = $post->search($query)->take(10)->get();
        $results->load('user');
        if ($results->isEmpty()) {
            Log::error('No posts found matching the search criteria: ' . $query);
            return response()->json(['message' => 'No posts found matching the search criteria.'], 404);
        }
        return SearchPostResource::collection($results);
    }

    public function searchUsersByUsername(User $user, Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3|exists:users,username',
        ]);

        $username = request()->input('username');
        $results = $user->search($username)->get();
        $results->load('posts');
        if ($results->isEmpty()) {
            Log::error('No users found matching the search criteria: ' . $username);
            return response()->json(['message' => 'No users found matching the search criteria.'], 404);
        }
        return response()->json([
            'user' => SearchUsersResource::collection($results),
        ]);
    }

    public function searchTagsName(Tag $tag, Request $request)
    {
        $request->validate([
            'tag' => 'required|string|min:3|exists:tags,name',
        ]);

        $tagName = request()->input('tag');
        $results = $tag->search($tagName)->get();
        $results->load(['posts']);
        if ($results->isEmpty()) {
            return response()->json(['message' => 'No Tags found matching the search criteria.'], 404);
        }
        return response()->json([
            'tags' => SearchTagsResource::collection($results),
        ]);
    }

}
