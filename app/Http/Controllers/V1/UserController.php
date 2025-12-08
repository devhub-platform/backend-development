<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UsersCollection;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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

    public function show(User $user)
    {
        $this->authorize('view', $user);
        return response()->json([
            'user' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $user->delete();
        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    public function userPosts(User $user)
    {
        $this->authorize('user-posts', $user);
        $posts = $user->posts()->get();
        return response()->json([
            'posts' => PostResource::collection($posts),
        ]);
    }

    public function userComments(User $user)
    {
        $this->authorize('user-comments', $user);
        $comments = $user->comments()->get();
        return response()->json([
            'comments' => CommentResource::collection($comments),
        ]);
    }
}
