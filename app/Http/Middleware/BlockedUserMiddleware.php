<?php

namespace App\Http\Middleware;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BlockedUserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        $contentOwner = $this->getContentOwner($request);

        if ($contentOwner && $this->isBlocked($user, $contentOwner)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot perform this action. Access to this content is restricted.',
            ], 403);
        }

        return $next($request);
    }

    private function isBlocked(User $currentUser, User $contentOwner): bool
    {
        return $currentUser->isBlockedBy($contentOwner) || $currentUser->hasBlocked($contentOwner);
    }


    private function getContentOwner(Request $request): ?User
    {
        if ($user = $request->route('user')) {
            return $user instanceof User ? $user : User::find($user);
        }

        if ($post = $request->route('post')) {
            $post = $post instanceof Post ? $post : Post::find($post);
            return $post?->user;
        }

        if ($comment = $request->route('comment')) {
            $comment = $comment instanceof Comment ? $comment : Comment::find($comment);
            return $comment?->user;
        }

        if ($username = $request->route('username')) {
            return User::where('username', $username)->first();
        }

        if ($userId = $request->input('user_id')) {
            return User::find($userId);
        }

        if ($targetUserId = $request->input('target_user_id')) {
            return User::find($targetUserId);
        }

        return null;
    }
}

