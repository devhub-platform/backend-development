<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\SuggestedUsersResource;
use App\Models\User;
use App\Notifications\FollowNotification;
use App\Services\Followers\PeopleSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use OneSignal;

class FollowersController
{
    public function follow(User $user)
    {
        $authUser = Auth::user();

        if ($authUser->id === $user->id) {
            return response()->json([
                'error' => 'You cannot follow yourself.'
            ], 400);
        }

        if ($authUser->following()->where('following_id', $user->id)->exists()) {
            return response()->json([
                'error' => 'You are already following this user.'
            ], 400);
        }

        $authUser->following()->attach($user->id);

        $this->bumpSuggestionCacheVersion($authUser->id);

        if ($user->isNotificationEnabled('new_follower')) {
            Notification::send($user, new FollowNotification($authUser));

            // Only send OneSignal notification if player_id is set
            if (!empty($user->onesignal_player_id)) {
                OneSignal::sendNotificationToUser(
                    'You have a new follower',
                    $user->onesignal_player_id,
                    'deeplink://followers?id=' . $user->id,
                    null,
                    null,
                    null,
                    "{$authUser->name} started following you!"
                );
            }
        }
        Log::info("User {$authUser->id} followed user {$user->id}");

        return response()->json([
            'message' => "Successfully followed user {$user->name}"
        ]);
    }

    public function unfollow(User $user)
    {
        $authUser = auth()->user();

        if (!$authUser->following()->where('following_id', $user->id)->exists()) {
            return response()->json([
                'error' => 'You are not following this user.'
            ], 400);
        }

        $authUser->following()->detach($user->id);

        $this->bumpSuggestionCacheVersion($authUser->id);

        Log::notice("User {$authUser->id} unfollowed user {$user->id}");
        return response()->json([
            'message' => "Successfully unfollowed user {$user->name}"
        ]);
    }

    public function UserFollowStats(User $user)
    {
        $count_following = $user->following()->count();
        $count_followers = $user->followers()->count();

        return response()->json([
            'following_count' => $count_following,
            'followers_count' => $count_followers,
        ]);
    }

    public function myFollowers()
    {
        $user = auth()->user();
        $followers = $user->followers()->get(['users.id', 'users.name', 'users.username', 'users.avatar_url']);

        if ($followers->isEmpty()) {
            return response()->json([
                'message' => 'You have no followers yet.'
            ]);
        }

        return response()->json([
            'followers_count' => $followers->count(),
            'followers' => $followers->makeHidden('pivot'),
        ]);
    }

    public function myFollowing()
    {
        $user = auth()->user();
        $following = $user->following()->get(['users.id', 'users.name', 'users.username', 'users.avatar_url']);

        if ($following->isEmpty()) {
            return response()->json([
                'message' => 'You are not following anyone yet.'
            ]);
        }

        return response()->json([
            'following_count' => $following->count(),
            'following' => $following->makeHidden('pivot'),
        ]);
    }

    public function suggestions(Request $request)
    {
        $user = Auth::user();
        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min($limit, 20));

        $version = (int) Cache::get($this->suggestionVersionKey($user->id), 1);
        $cacheKey = "user_suggestions_{$user->id}_v{$version}_{$limit}";
        $suggestedUsers = Cache::remember($cacheKey, 1800, function () use ($user, $limit) {
            return app(PeopleSuggestionService::class)->suggestForUser($user, $limit);
        });

        if ($suggestedUsers->isEmpty()) {
            return response()->json([
                'message' => 'No user suggestions available at the moment.'
            ]);
        }

        return response()->json([
            //            'suggested_users_count' => $suggestedUsers->count(),
            'suggested_users' => SuggestedUsersResource::collection($suggestedUsers),
        ]);
    }

    private function suggestionVersionKey(int $userId): string
    {
        return "user_suggestions_version_{$userId}";
    }

    private function bumpSuggestionCacheVersion(int $userId): void
    {
        $versionKey = $this->suggestionVersionKey($userId);

        if (!Cache::has($versionKey)) {
            Cache::forever($versionKey, 1);
        }

        Cache::increment($versionKey);
    }
}