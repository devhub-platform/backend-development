<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\SearchPostResource;
use App\Http\Resources\SearchTagsResource;
use App\Http\Resources\SearchUsersResource;
use App\Models\Post;
use App\Models\SearchHistory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchController
{
    private function storeHistory($query)
    {
        $userId = auth()->id();
        if (!$userId || empty($query) || trim($query) === "") {
            return;
        }

        $existing = SearchHistory::where('user_id', $userId)
            ->where('query', $query)
            ->first();

        if ($existing) {
            $existing->update([
                'updated_at' => now(),
            ]);

        } else {
            SearchHistory::create([
                'user_id' => $userId,
                'query' => $query,
            ]);
        }

        SearchHistory::where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->skip(10)
            ->take(PHP_INT_MAX)
            ->delete();
    }

    public function searchPosts(Request $request, Post $post)
    {
        $query = $request->input('q')
            ?? $request->input('query')
            ?? $request->input('search')
            ?? $request->input('post');

        if (!$query) {
            return response()->json(['message' => 'Query is required'], 422);
        }

        $results = $post->search($query)->take(10)->get();

        if ($results->isNotEmpty()) {
            $results->load('user');
            SearchHistory::create([
                'user_id' => auth()->id(),
                'query' => $query,
            ]);
        }

        if ($results->isEmpty()) {
            Log::error("No posts found matching: $query");
            return response()->json(['message' => 'No posts found'], 404);
        }

        return SearchPostResource::collection($results);
    }


    public function searchUsersByUsername(User $user, Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3|exists:users,username',
        ]);

        $username = $request->input('username');
        $results = $user->search($username)->get();
        if ($results->isNotEmpty()) {
            $results->load('posts');
            SearchHistory::create([
                'user_id' => auth()->id(),
                'query' => $username,
            ]);
        }
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

        $tagName = $request->input('tag');
        $results = $tag->search($tagName)->get();
        if ($results->isNotEmpty()) {
            $results->load('posts');
            SearchHistory::create([
                'user_id' => auth()->id(),
                'query' => $tagName,
            ]);
        }
        if ($results->isEmpty()) {
            return response()->json(['message' => 'No Tags found matching the search criteria.'], 404);
        }
        return response()->json([
            'tags' => SearchTagsResource::collection($results),
        ]);
    }

    public function globalSearch(Request $request, Post $post, User $user, Tag $tag)
    {
        $request->validate([
            'query' => 'required|string|min:3',
        ]);

        $query = $request->input('query');

        $postResults = $post->search($query)->take(5)->get();
        if ($postResults->isNotEmpty()) {
            $postResults->load('user');
        }

        $userResults = $user->search($query)->take(5)->get();
        if ($userResults->isNotEmpty()) {
            $userResults->load('posts');
        }

        $tagResults = $tag->search($query)->take(5)->get();
        if ($tagResults->isNotEmpty()) {
            $tagResults->load('posts');
        }

        return response()->json([
            'posts' => SearchPostResource::collection($postResults),
            'users' => SearchUsersResource::collection($userResults),
            'tags' => SearchTagsResource::collection($tagResults),
        ]);
    }

    public function searchHistories()
    {
        $user = auth()->user();
        $histories = SearchHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        return response()->json([
            'Searching Data:' => $histories->pluck('query'),
        ]);
    }

    public function clearSearch(SearchHistory $history)
    {
        $user = auth()->user();
        SearchHistory::where('user_id', $user->id)->delete();
        Log::info('Search history cleared for user: ' . $user->email);
        return response()->json([
            'message' => 'Search history cleared successfully',
        ]);
    }


}
