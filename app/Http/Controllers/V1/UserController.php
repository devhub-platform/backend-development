<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\CommentResource;
use App\Http\Resources\TrendingPostResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UsersCollection;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $users = User::paginate(15);

        return response()->json([
            'users' => new UsersCollection($users),
        ]);
    }

    public function showUserProfile(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function userPosts(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $perPage = $request->query('per_page', 15);
        $status = $request->query('status');

        $query = $user->posts()
            ->with('tags', 'user')
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $posts = $query->paginate($perPage);

        return response()->json([
            'data' => TrendingPostResource::collection($posts),
            'pagination' => $this->getPaginationData($posts),
        ]);
    }

    public function userComments(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $perPage = $request->query('per_page', 15);

        $comments = $user->comments()
            ->with('post', 'user')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => CommentResource::collection($comments),
            'pagination' => $this->getPaginationData($comments),
        ]);
    }

    /**
     * Get followed tags by user
     */
    public function userTags(Request $request, User $user): JsonResponse
    {
        $perPage = $request->query('per_page', 15);

        $tags = $user->followedTags()->paginate($perPage);

        return response()->json([
            'data' => $tags->items(),
            'pagination' => $this->getPaginationData($tags),
        ]);
    }

    public function usersFollowing(User $user): JsonResponse
    {
        $following = $user->following()->with('followers')->get();

        if ($following->isEmpty()) {
            return response()->json([
                'message' => 'This user is not following anyone yet.',
            ]);
        }

        return response()->json([
            'following' => UserResource::collection($following),
        ]);
    }

    public function usersFollowers(User $user): JsonResponse
    {
        $followers = $user->followers()->with('following')->get();

        if ($followers->isEmpty()) {
            return response()->json([
                'message' => 'This user has no followers yet.',
            ]);
        }

        return response()->json([
            'followers' => UserResource::collection($followers),
        ]);
    }

    public function usersFollowersCount(User $user): JsonResponse
    {
        $count = $user->followers()->count();

        if ($count === 0) {
            return response()->json([
                'message' => 'This user has no followers yet.',
            ]);
        }

        return response()->json([
            'followers_count' => $count,
        ]);
    }

    public function getMutualFollowers(int $userId): JsonResponse
    {
        $authUser = auth()->user();

        if (!$authUser) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }

        $targetUser = User::findOrFail($userId);

        $mutualFollowerIds = $authUser->followers()
            ->pluck('users.id')
            ->intersect($targetUser->followers()->pluck('users.id'));

        $mutualFollowers = User::whereIn('id', $mutualFollowerIds)->get();

        return response()->json([
            'count' => $mutualFollowers->count(),
            'data' => UserResource::collection($mutualFollowers),
        ]);
    }

    public function getRecommendedUsers(Request $request): JsonResponse
    {
        $authUser = auth()->user();

        if (!$authUser) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }

        $limit = $request->query('limit', 10);

        $authUserFollowerIds = $authUser->followers()
            ->pluck('users.id');

        $alreadyFollowingIds = $authUser->following()
            ->pluck('users.id');

        // Get followers of user's followers (people they have mutual connections with)
        $followersOfFollowers = User::whereHas('followers', function ($query) use ($authUserFollowerIds) {
            $query->whereIn('follower_id', $authUserFollowerIds);
        })
            ->whereNotIn('id', [$authUser->id])
            ->whereNotIn('id', $alreadyFollowingIds)
            ->limit($limit)
            ->get();

        return response()->json([
            'count' => $followersOfFollowers->count(),
            'data' => UserResource::collection($followersOfFollowers),
        ]);
    }

    public function getUsersWithSimilarSkills(int $userId, Request $request): JsonResponse
    {
        $user = User::findOrFail($userId);
        $limit = $request->query('limit', 10);

        if (!$user->skills) {
            return response()->json([
                'message' => 'This user has not specified any skills.',
                'data' => [],
            ]);
        }

        // Find users with overlapping skills
        $similarUsers = User::where('id', '!=', $user->id)
            ->whereNotNull('skills')
            ->limit($limit)
            ->get()
            ->filter(function ($u) use ($user) {
                $userSkills = is_array($user->skills) ? $user->skills : [];
                $otherSkills = is_array($u->skills) ? $u->skills : [];
                return count(array_intersect($userSkills, $otherSkills)) > 0;
            });

        return response()->json([
            'user_id' => $userId,
            'user_skills' => $user->skills ?? [],
            'count' => $similarUsers->count(),
            'data' => UserResource::collection($similarUsers),
        ]);
    }

    public function checkMutualFollowing(int $userId): JsonResponse
    {
        $authUser = auth()->user();

        if (!$authUser) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 401);
        }

        $targetUser = User::findOrFail($userId);

        $authFollowingTarget = $authUser->following()->where('following_id', $userId)->exists();
        $targetFollowingAuth = $targetUser->following()->where('following_id', $authUser->id)->exists();

        return response()->json([
            'user_id' => $authUser->id,
            'target_user_id' => $userId,
            'auth_following_target' => $authFollowingTarget,
            'target_following_auth' => $targetFollowingAuth,
            'mutual_following' => $authFollowingTarget && $targetFollowingAuth,
        ]);
    }

    private function getPaginationData(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
