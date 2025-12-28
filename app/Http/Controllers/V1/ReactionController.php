<?php

namespace App\Http\Controllers\V1;

use App\Models\Post;
use App\Notifications\ReactNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ReactionController
{
    public int $numberOfReactions = 0;
    public function reactToPost(Request $request, $postId)
    {
        $request->validate([
            'type' => 'required|string|max:50|in:like,sad,love,angry,wow,haha',
        ]);

        $post = Post::findOrFail($postId);
        $user = auth()->user();

        $user->reaction($request->type, $post);
        $reactType = $request->type;
        Notification::send($post->user, new ReactNotification($post, $reactType));

        return response()->json([
            'message' => 'Reaction added successfully',
            'reaction' => $request->type,
        ]);
    }

    public function removeReaction(Post $post)
    {
        $user = Auth::user(); // return the authenticated user
        $user->removeReactions($post);
        return response()->json([
            'message' => 'Reaction removed successfully'
        ]);
    }

    public function getReactors(Post $post)
    {
        $users = $post->getReactors();
        return response()->json([
            'post' => $post->title,
            'reactors' => $users,
        ]);
    }

    public function myReaction(Post $post)
    {
        $user = auth()->user();
        $reaction = $user->myReaction($post);

        return response()->json([
            'reaction' => $reaction?->type ?? null
        ]);
    }

    public function reactionCounts(Post $post)
    {
        $reactionCounts = $post->getReactionsWithCount();
        return response()->json([
            'post title' => $post->title,
            'all reactions count' => $reactionCounts,
        ]);
    }

    public function getTotalReactionsOnPosts()
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $totalReactions = (int)$user->posts()->withCount('reactions')->get()->sum('reactions_count');
        if (!is_numeric($totalReactions) || $totalReactions < 0) {
            $totalReactions = 0;
        }

        return response()->json([
            'user' => $user->name,
            'total_reactions_on_posts' => $totalReactions,
        ]);
    }

}