<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UsersCollection;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = User::paginate(15);
        return response()->json([
            'users' => new UsersCollection($users),
        ]);
    }

    public function showUserProfile(int $userId)
    {
        $user = User::findOrFail($userId);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function userPosts(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);
        $perPage = $request->query('per_page', 15);
        $status = $request->query('status', null);

        $query = $user->posts()->with('tags', 'user')->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $posts = $query->paginate($perPage);

        return response()->json([
            'data' => PostResource::collection($posts),
            'pagination' => [
                'total' => $posts->total(),
                'per_page' => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    public function userComments(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);
        $perPage = $request->query('per_page', 15);

        $comments = $user->comments()
            ->with('post', 'user')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => CommentResource::collection($comments),
            'pagination' => [
                'total' => $comments->total(),
                'per_page' => $comments->perPage(),
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }
}
