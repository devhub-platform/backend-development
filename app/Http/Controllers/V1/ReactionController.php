<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ReactionRequest;
use App\Models\Post;
use App\Notifications\ReactNotification;
use App\Services\InteractionLoggerService;
use App\Services\UserInterestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use OneSignal;

class ReactionController
{
    public int $numberOfReactions = 0;

    protected UserInterestService $userInterestService;
    protected InteractionLoggerService $interactionLoggerService;

    public function __construct(UserInterestService $userInterestService, InteractionLoggerService $interactionLoggerService)
    {
        $this->userInterestService = $userInterestService;
        $this->interactionLoggerService = $interactionLoggerService;
    }

    public function reactToPost(ReactionRequest $request, $postId)
    {
        $validated = $request->validated();

        $post = Post::with('user', 'tags')->findOrFail($postId);
        $user = auth()->user();

        if ($post->user && $user->isBlockedWith($post->user)) {
            return response()->json([
                'message' => 'You cannot interact with this content.',
            ], 403);
        }

        $existingReaction = $user->myReaction($post);
        if ($existingReaction) {
            if ($existingReaction->type === $validated['type']) {
                return response()->json([
                    'message' => 'You have already reacted with this type.',
                ], 409);
            }

            $user->updateReaction($validated['type'], $post);
            if ($post->user->isNotificationEnabled('new_reaction')) {
                Notification::send($post->user, new ReactNotification($post, $validated['type'], $user));
                OneSignal::sendNotificationToUser(
                    'Your post received a new reaction',
                    $post->user->onesignal_player_id,
                    'deeplink://reactions?id=' . $post->id,
                    null,
                    null,
                    null
                    ,
                    'Your post received a new reaction'
                );
            }

            // Track the interaction with full reporting
            $this->userInterestService->trackPostInteraction(
                $user,
                $post,
                $validated['type'],
                json_encode(['reaction_update' => true])
            );

            $tagsString = $post->tags->pluck('name')->implode(', ');
            $this->interactionLoggerService->logInteraction(
                userId: $user->id,
//                articleUuid: (string) $post->uuid ?? null,
                category: $tagsString ?: 'Article',
                action: 'like',
                duration: 50
            );

            return response()->json([
                'message' => 'Reaction updated successfully.',
                'reaction' => $validated['type'],
            ], 200);
        }

        $user->reaction($validated['type'], $post);
        if ($post->user->isNotificationEnabled('new_reaction')) {
            Notification::send($post->user, new ReactNotification($post, $validated['type'], $user));
        }

        // Track the interaction with full reporting
        $result = $this->userInterestService->trackPostInteraction(
            $user,
            $post,
            $validated['type'],
            json_encode(['new_reaction' => true])
        );

        $tagsString = $post->tags->pluck('name')->implode(', ');
        $this->interactionLoggerService->logInteraction(
            userId: $user->id,
            category: $tagsString ?: 'Article',
            action: 'like',
            duration: 0
        );

        return response()->json([
            'message' => 'Reaction added successfully.',
            'reaction' => $validated['type'],
            'topic_tracking' => $result, // Show what topics were tracked/added
        ], 201);
    }

    public function removeReaction(Post $post)
    {
        $user = Auth::user();
        if ($post->user && $user->isBlockedWith($post->user)) {
            return response()->json([
                'message' => 'You cannot interact with this content.',
            ], 403);
        }
        $user->removeReactions($post);
        return response()->json([
            'message' => 'Reaction removed successfully'
        ]);
    }

    public function getReactors(Post $post)
    {
        $users = $post->getReactors()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar_url,
                'reaction_time' => $user->pivot->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'post' => $post->title,
            'reactors' => $users,
        ]);
    }

    public function myReaction(Post $post)
    {
        $user = auth()->user();
        if ($post->user && $user->isBlockedWith($post->user)) {
            return response()->json([
                'reaction' => null,
                'message' => 'You cannot interact with this content.',
            ], 403);
        }
        $reaction = $user->myReaction($post);

        return response()->json([
            'reaction' => $reaction?->type ?? null
        ]);
    }

    public function reactionCounts(Post $post)
    {
        if (auth()->check() && $post->user && auth()->user()->isBlockedWith($post->user)) {
            return response()->json([
                'message' => 'You cannot interact with this content.',
            ], 403);
        }
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
