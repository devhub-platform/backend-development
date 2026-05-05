<?php

namespace App\Services;

use App\Models\Post;
use App\Models\SearchHistory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SearchService
{
    private const HISTORY_LIMIT = 10;
    private const SEARCH_LIMIT = 5;
    private const CACHE_MINUTES = 60;
    private const MAX_QUERY_LEN = 100;

    public function searchPosts(Request $request, Post $post): array
    {
        $query = $this->validateQuery($request, 'query');

        $this->storeHistory($query);

        $results = $post->search($query)
            ->take(self::SEARCH_LIMIT)
            ->get()
            ->load('user');

        return $this->result($results);
    }

    public function searchUsers(Request $request, User $user): array
    {
        $query = $this->validateQuery($request, 'username');

        $this->storeHistory($query);

        $results = $user->search($query)
            ->take(self::SEARCH_LIMIT)
            ->get()
            ->load('posts');

        return $this->result($results);
    }

    public function searchTags(Request $request, Tag $tag): array
    {
        $query = $this->validateQuery($request, 'tag');

        $this->storeHistory($query);

        $results = $tag->search($query)
            ->take(self::SEARCH_LIMIT)
            ->get()
            ->load('posts');

        return $this->result($results);
    }

    public function globalSearch(
        Request $request,
        Post    $post,
        User    $user,
        Tag     $tag
    ): array
    {
        $query = $this->validateQuery($request, 'query');

        $this->storeHistory($query);

        return [
            'posts' => $post->search($query)->take(self::SEARCH_LIMIT)->get()->load('user'),
            'users' => $user->search($query)->take(self::SEARCH_LIMIT)->get()->load('posts'),
            'tags' => $tag->search($query)->take(self::SEARCH_LIMIT)->get()->load('posts'),
        ];
    }


    public function getSearchHistory(): array
    {
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $data = Cache::remember(
            "search_histories_{$user->id}",
            now()->addMinutes(self::CACHE_MINUTES),
            fn() => SearchHistory::where('user_id', $user->id)
                ->orderByDesc('updated_at')
                ->take(self::HISTORY_LIMIT)
                ->pluck('query')
        );

        return [
            'message' => 'Search history retrieved successfully',
            'count' => $data->count(),
            'data' => $data,
        ];
    }

    public function clearSearchHistory(): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        SearchHistory::where('user_id', $user->id)->delete();
        Cache::forget("search_histories_{$user->id}");
    }

    private function validateQuery(Request $request, string $key): string
    {
        return $request->validate([
            $key => "required|string|min:3|max:" . self::MAX_QUERY_LEN,
        ])[$key];
    }

    private function storeHistory(string $query): void
    {
        $userId = auth()->id();
        if (!$userId) return;

        try {
            SearchHistory::updateOrCreate(
                ['user_id' => $userId, 'query' => trim($query)],
                ['updated_at' => now()]
            );

            Log::info('Search history user:', [
                'user_id' => auth()->id(),
                'query' => $query,
            ]);

            SearchHistory::where('user_id', $userId)
                ->whereNotIn(
                    'id',
                    SearchHistory::where('user_id', $userId)
                        ->orderByDesc('updated_at')
                        ->take(self::HISTORY_LIMIT)
                        ->pluck('id')
                )->delete();

            Cache::forget("search_histories_{$userId}");
        } catch (\Throwable $e) {
            Log::warning('Search history failed: ' . $e->getMessage());
        }
    }

    private function result(Collection $results): array
    {
        return [
            'count' => $results->count(),
            'results' => $results,
        ];
    }
}
