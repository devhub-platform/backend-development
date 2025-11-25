<?php

namespace App\Http\Controllers\V1;

use App\Models\Post;
use App\Notifications\ReactNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class ReactionController
{
    public function reactToPost(Request $request, $postId)
    {
        $request->validate([
            'type' => 'required|string|max:50'
        ]);

        $post = Post::findOrFail($postId);
        $user = auth()->user();

        $user->reaction($request->type,$post );
        $reactType = $request->type;
        Notification::send($post->user, new ReactNotification($post , $reactType));
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
        return response()->json([
            'post_id' => $post->title,
            'reactors' => $post->getReactors(),
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
            'post_id' => $post->title,
            'all_reaction_counts' => $reactionCounts,
        ]);
    }
}
