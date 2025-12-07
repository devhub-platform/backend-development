<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\FollowNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FollowersController
{
    public function follow(User $user)
    {
        $authUser = auth()->user();
        $authUser->following()->attach($user->id);
        Notification::send($user, new FollowNotification($user));
        Log::info('Followed ' . $user->name);
        return response()->json([
            'message' => "Successfully followed user {$user->name}"
        ]);
    }

    public function unfollow(User $user)
    {
        $authUser = auth()->user();
        $authUser->following()->detach($user->id);
        Log::info('Unfollowed ' . $user->name);
        return response()->json([
            'message' => 'Successfully unfollowed user'
        ]);
    }

    public function following(User $user)
    {
        $following = $user->following()->get();
        if ($following->isEmpty()) {
            return response()->json([
                'message' => 'This user is not following anyone yet.'
            ]);
        }
        return response()->json([
            'Following' => UserResource::collection($following),
        ]);
    }

    public function followers(User $user)
    {
        $followers = $user->followers()->get();
        if ($followers->isEmpty()) {
            return response()->json([
                'message' => 'This user has no followers yet.'
            ]);
        }
        return response()->json([
            'Followers' => UserResource::collection($followers),
        ]);
    }

    public function followersCount(User $user)
    {
        $count = $user->followers()->count();
        return response()->json([
            'followers_count' => $count,
        ]);
    }

    public function followingCount(User $user)
    {
        $count = $user->following()->count();
        return response()->json([
            'following_count' => $count,
        ]);
    }

}
