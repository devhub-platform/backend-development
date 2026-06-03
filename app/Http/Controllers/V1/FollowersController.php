<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\SuggestedUsersResource;
use App\Models\User;
use App\Notifications\FollowNotification;
use App\Services\Followers\PeopleSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use OneSignal;

class FollowersController
{
    public function follow(User $user)
    {
        $authUser = Auth::user();

        if ($authUser->id === $user->id) {
            return response()->json([
                'error' => 'You cannot follow yourself.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($authUser->following()->where('following_id', $user->id)->exists()) {
            return response()->json([
                'error' => 'You are already following this user.'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $authUser->following()->attach($user->id);
            $this->invalidateFollowerCaches($authUser->id, $user->id);
            $this->sendFollowNotifications($user, $authUser);

            DB::commit();

            Log::info("Follow action", [
                'follower_id' => $authUser->id,
                'following_id' => $user->id,
            ]);

            return response()->json([
                'message' => "Successfully followed {$user->name}"
                // 'data' => [
                //     'user_id' => $user->id,
                //     'status' => 'following',
                // ]
            ], Response::HTTP_OK);

        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error("Follow action failed", [
                'follower_id' => $authUser->id,
                'following_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to follow user.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function unfollow(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->following()->where('following_id', $user->id)->exists()) {
            return response()->json([
                'error' => 'You are not following this user.'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $authUser->following()->detach($user->id);
            $this->invalidateFollowerCaches($authUser->id, $user->id);

            DB::commit();

            Log::info("Unfollow action", [
                'follower_id' => $authUser->id,
                'following_id' => $user->id,
            ]);

            return response()->json([
                'message' => "Successfully unfollowed {$user->name}"
                // 'data' => [
                //     'user_id' => $user->id,
                //     'status' => 'not_following',
                // ]
            ], Response::HTTP_OK);

        } catch (\Throwable $exception) {
            DB::rollBack();

            Log::error("Unfollow action failed", [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to unfollow user.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function UserFollowStats(User $user)
    {
        $cacheKey = "follow_stats_user_{$user->id}";

        $stats = Cache::remember($cacheKey, 3600, function () use ($user) {
            return [
                'user_id' => $user->id,
                'following_count' => $user->following()->count(),
                'followers_count' => $user->followers()->count(),
                'mutual_count' => $this->getMutualFollowCount($user),
            ];
        });

        return response()->json($stats, Response::HTTP_OK);
    }

    public function myFollowers(Request $request)
    {
        $user = Auth::user();
        $page = $request->query('page', 1);
        $perPage = min($request->query('per_page', 20), 100);

        $followers = $user->followers()
            ->paginate($perPage, ['users.id', 'users.name', 'users.username', 'users.avatar_url', 'users.verified'], 'page', $page);

        if ($followers->isEmpty()) {
            return response()->json([
                'message' => 'You have no followers yet.',
                'data' => []
            ]);
        }

        return response()->json([
            'followers_count' => $followers->total(),
            'page' => $page,
            'per_page' => $perPage,
            'followers' => $followers->map(fn($f) => $f->makeHidden('pivot'))->all()
        ]);
    }

    public function myFollowing(Request $request)
    {
        $user = Auth::user();
        $page = $request->query('page', 1);
        $perPage = min($request->query('per_page', 20), 100);

        $following = $user->following()
            ->paginate($perPage, ['users.id', 'users.name', 'users.username', 'users.avatar_url', 'users.verified'], 'page', $page);

        if ($following->isEmpty()) {
            return response()->json([
                'message' => 'You are not following anyone yet.',
                'data' => []
            ]);
        }

        return response()->json([
            'following_count' => $following->total(),
            'page' => $page,
            'per_page' => $perPage,
            'following' => $following->map(fn($f) => $f->makeHidden('pivot'))->all()
        ]);
    }

    public function suggestions(Request $request)
    {
        $user = Auth::user();

        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min($limit, 50));

        $refresh = $request->query('refresh', false) === 'true';

        try {
            $version = (int) Cache::get($this->suggestionVersionKey($user->id), 1);
            $cacheKey = "user_suggestions_{$user->id}_v{$version}_{$limit}";

            if ($refresh) {
                Cache::forget($cacheKey);
                $this->bumpSuggestionCacheVersion($user->id);
                $version = (int) Cache::get($this->suggestionVersionKey($user->id), 1);
                $cacheKey = "user_suggestions_{$user->id}_v{$version}_{$limit}";
            }

            $suggestedUsers = Cache::remember(
                $cacheKey,
                1800,
                function () use ($user, $limit) {
                    return app(PeopleSuggestionService::class)->suggestForUser($user, $limit);
                }
            );

            if ($suggestedUsers->isEmpty()) {
                return response()->json([
                    'message' => 'No suggestions available.',
                    'data' => [],
                    'count' => 0
                ], Response::HTTP_OK);
            }

            Log::info('Suggestions fetched', [
                'user_id' => $user->id,
                'count' => $suggestedUsers->count(),
            ]);

            return response()->json([
                'suggested_users' => SuggestedUsersResource::collection($suggestedUsers),
                'count' => $suggestedUsers->count(),
                'limit' => $limit,
            ], Response::HTTP_OK);

        } catch (\Throwable $exception) {
            Log::error('Suggestions service error', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch suggestions.',
                'data' => []
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function sendFollowNotifications(User $user, User $follower): void
    {
        try {
            if ($user->isNotificationEnabled('new_follower')) {
                Notification::send($user, new FollowNotification($follower));

                if (!empty($user->onesignal_player_id)) {
                    OneSignal::sendNotificationToUser(
                        'You have a new follower',
                        $user->onesignal_player_id,
                        'deeplink://profile?id=' . $follower->id,
                        ['follower_id' => $follower->id],
                        null,
                        null,
                        "{$follower->name} started following you!"
                    );
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('Failed to send notifications', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function invalidateFollowerCaches(int $followerId, int $followingId): void
    {
        Cache::forget("follow_stats_user_{$followerId}");
        Cache::forget("follow_stats_user_{$followingId}");
        $this->bumpSuggestionCacheVersion($followerId);
        $this->bumpSuggestionCacheVersion($followingId);
    }

    private function getMutualFollowCount(User $user): int
    {
        $followingIds = $user->following()->pluck('users.id')->all();

        if (empty($followingIds)) {
            return 0;
        }

        return $user->followers()
            ->whereIn('users.id', $followingIds)
            ->count();
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
        } else {
            Cache::increment($versionKey);
        }
    }
}
