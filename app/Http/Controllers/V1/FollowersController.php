<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\FollowNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;

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

        if ($user->isNotificationEnabled('new_follower')) {
            Notification::send($user, new FollowNotification($authUser));
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

        Log::notice("User {$authUser->id} unfollowed user {$user->id}");
        return response()->json([
            'message' => "Successfully unfollowed user {$user->name}"
        ]);
    }

    public function followingCount(User $user)
    {
        $count = $user->following()->count();

        if ($count === 0) {
            return response()->json([
                'message' => 'This user is not following anyone yet.'
            ]);
        }

        return response()->json([
            'following_count' => $count,
        ]);
    }

    public function myFollowers()
    {
        $user = auth()->user();
        $followers = $user->followers()->get(['name', 'username', 'avatar_url']);

        if ($followers->isEmpty()) {
            return response()->json([
                'message' => 'You have no followers yet.'
            ]);
        }

        return response()->json([
            'number of followers: ' => $followers->count(),
            'Followers' => $followers->makeHidden('pivot'),

        ]);
    }

    public function myFollowing()
    {
        $user = auth()->user();
        $following = $user->following()->get(['name', 'username', 'avatar_url']);

        if ($following->isEmpty()) {
            return response()->json([
                'message' => 'You are not following anyone yet.'
            ]);
        }

        return response()->json([
            'number of following: ' => $following->count(),
            'Following' => $following->makeHidden('pivot'),
        ]);
    }

    public function suggestions()
    {
        $user = auth()->user();
        $followingIds = $user->following()->pluck('users.id')->toArray();
        $suggestedUsers = User::whereNotIn('id', array_merge($followingIds, [$user->id]))
            ->inRandomOrder()
            ->take(5)
            ->get(['id', 'name', 'username', 'bio']);

        if ($suggestedUsers->isEmpty()) {
            return response()->json([
                'message' => 'No user suggestions available at the moment.'
            ]);
        }

        return response()->json([
            'Suggested Users' => $suggestedUsers,
        ]);
    }
}