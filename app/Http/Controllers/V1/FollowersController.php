<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\SuggestedUsersResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\FollowNotification;
use App\Services\Followers\PeopleSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
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

        if ($user->isNotificationEnabled('new_follower')) {
            Notification::send($user, new FollowNotification($authUser));
//            OneSignal::sendNotificationToUser(
//                'You have a new follower',
//                $user->onesignal_player_id,
//                'deeplink://followers?id=' . $user->id,
//                null,
//                null,
//                "{$user->name} started following you!"
//            );
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

    public function suggestions(Request $request)
    {
        $user = Auth::user();
        $limit = (int)$request->query('limit', 5);
        $suggestedUsers = app(PeopleSuggestionService::class)->suggestForUser($user, $limit);

        if ($suggestedUsers->isEmpty()) {
            return response()->json([
                'message' => 'No user suggestions available at the moment.'
            ]);
        }

        return response()->json([
            'Suggested_users' => SuggestedUsersResource::collection($suggestedUsers),
        ]);
    }
}
