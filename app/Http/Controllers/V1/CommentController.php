<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\CommentsRequests\StoreCommentRequest;
use App\Http\Requests\CommentsRequests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\MentionInCommentNotification;
use App\Notifications\NewCommentNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Services\ModerationService;
use Illuminate\Support\Str;
use OneSignal;
class CommentController
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Comment::class);
        $comments = Comment::with(['user', 'post'])
            ->withCount('replies')
            ->pinnedFirst()
            ->latest()
            ->paginate(15);

        return response()->json([
            'comments' => CommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ]
        ]);
    }

    public function store(StoreCommentRequest $request, Post $post, ModerationService $moderationService)
    {
        $this->authorize('create', Comment::class);
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $validated['post_id'] = $post->id;

        $check_content = $moderationService->moderateContent($validated['content']);

        if ($check_content['flagged'] ?? false) {
            $moderation = $moderationService->getModerationMessage($check_content);
            Log::warning("Comment content flagged by moderation service for user ID: " . auth()->id() . ' ' . $moderation);

            return response()->json([
                'message' => 'Comment content violates our content policies and cannot be created , your account may be reviewed , and we take action against it.',
                'reasons' => $moderation
            ], 422);
        }

        $post = Post::with('user')->findOrFail($validated['post_id']);
        $comment = Comment::create($validated);

        // Send notification to post author if any one comment in his post, if author comment in his own post do not notify
        if ($post->user_id !== auth()->id() && $post->user->isNotificationEnabled('new_comment')) {
            Notification::send($post->user, new NewCommentNotification($comment));

            if (!empty($post->user->onesignal_player_id)) {
                try {
                    OneSignal::sendNotificationToUser(
                        Str::limit($validated['content'], 50),
                        $post->user->onesignal_player_id,
                        'deeplink://comments?id=' . $comment->id,
                        null,
                        null,
                        null,
                        'You have a new comment on your post "' . Str::limit($post->title, 20) . '"'
                    );
                } catch (\Throwable $e) {
                    Log::warning('Failed to send OneSignal comment notification', [
                        'post_id' => $post->id,
                        'comment_id' => $comment->id,
                        'user_id' => $post->user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->notifyMentionedUsers($comment, auth()->user());

        return response()->json([
            'message' => "Comment created successfully",
            'comment' => new CommentResource($comment->load('user'))
        ], 201);
    }

    public function show(Comment $comment)
    {
        $this->authorize('view', $comment);
        $comment->load(['user', 'replies.user', 'post']);
        $comment->loadCount('replies');

        return response()->json([
            'comment' => new CommentResource($comment)
        ]);
    }

    public function update(UpdateCommentRequest $request, Comment $comment, ModerationService $moderationService)
    {
        $this->authorize('update', $comment);
        $validated = $request->validated();

        if (isset($validated['content'])) {
            $check_content = $moderationService->moderateContent($validated['content']);

            if ($check_content['flagged'] ?? false) {
                $moderation = $moderationService->getModerationMessage($check_content);
                Log::warning("Comment update flagged by moderation service for user ID: " . auth()->id() . ' ' . $moderation);

                return response()->json([
                    'message' => 'Updated comment content violates our content policies.',
                    'reasons' => $moderation
                ], 422);
            }
        }

        $comment->update($validated);

        return response()->json([
            'message' => "Comment updated successfully",
            'comment' => new CommentResource($comment->fresh()->load('user'))
        ]);
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);
        $comment->delete();

        return response()->json([
            'message' => "Comment deleted successfully"
        ]);
    }

    public function forceDelete($id)
    {
        $comment = Comment::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $comment);

        $comment->forceDelete();

        return response()->json([
            'message' => "Comment permanently deleted"
        ]);
    }

    public function restore($id)
    {
        $comment = Comment::withTrashed()->findOrFail($id);
        $this->authorize('restore', $comment);

        $comment->restore();

        return response()->json([
            'message' => "Comment restored successfully",
            'comment' => new CommentResource($comment->load('user'))
        ]);
    }

    public function getByPost(Request $request, $postId)
    {
        $this->authorize('viewAny', Comment::class);

        $sortBy = $request->get('sort', 'latest'); // latest, oldest, popular
        $perPage = $request->get('per_page', 15);

        $query = Comment::where('post_id', $postId)
            ->topLevel()
            ->with(['user', 'replies.user'])
            ->withCount('replies');

        // Apply sorting
        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'popular':
                $query->withCount('reactions')->orderByDesc('reactions_count');
                break;
            case 'latest':
            default:
                $query->pinnedFirst()->latest();
                break;
        }

        $comments = $query->paginate($perPage);

        return response()->json([
            'comments' => CommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ]
        ]);
    }

    public function getByUser(Request $request, $userId)
    {
        $this->authorize('viewAny', Comment::class);

        $perPage = $request->get('per_page', 15);

        $comments = Comment::where('user_id', $userId)
            ->with(['post', 'user'])
            ->withCount('replies')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'comments' => CommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ]
        ]);
    }

    public function reply(StoreCommentRequest $request, Post $post, Comment $parentComment, ModerationService $moderationService)
    {
        $this->authorize('create', Comment::class);
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $validated['parent_id'] = $parentComment->id;
        $validated['post_id'] = $post->id;

        $check_content = $moderationService->moderateContent($validated['content']);

        if ($check_content['flagged'] ?? false) {
            $moderation = $moderationService->getModerationMessage($check_content);
            Log::warning("Comment content flagged by moderation service for user ID: " . auth()->id() . ' ' . $moderation);

            return response()->json([
                'message' => 'Comment content violates our content policies and cannot be created , your account may be reviewed , and we take action against it.',
                'reasons' => $moderation
            ], 422);
        }

        $comment = Comment::create($validated);

        // Notify the parent comment author
        if ($parentComment->user_id !== auth()->id() && $parentComment->user->isNotificationEnabled('new_comment')) {
            Notification::send($parentComment->user, new NewCommentNotification($comment));

            if (!empty($parentComment->user->onesignal_player_id)) {
                try {
                    OneSignal::sendNotificationToUser(
                        'You have a new reply on your comment: ' . Str::limit($validated['content'], 50),
                        $parentComment->user->onesignal_player_id,
                        'deeplink://comments?id=' . $comment->id,
                        null,
                        null,null,
                        "New reply from " . auth()->user()->name . " to Your Comment"
                    );
                } catch (\Throwable $e) {
                    Log::warning('Failed to send OneSignal reply notification', [
                        'parent_comment_id' => $parentComment->id,
                        'comment_id' => $comment->id,
                        'user_id' => $parentComment->user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Detect and notify mentioned users
        $this->notifyMentionedUsers($comment, auth()->user());

        return response()->json([
            'message' => "Reply created successfully",
            'comment' => new CommentResource($comment->load('user'))
        ], 201);
    }

    public function getReplies(Request $request, Comment $comment)
    {
        $this->authorize('view', $comment);

        $perPage = $request->get('per_page', 10);

        $replies = $comment->replies()
            ->with('user')
            ->withCount('replies')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'parent_comment_id' => $comment->id,
            'replies' => CommentResource::collection($replies),
            'meta' => [
                'current_page' => $replies->currentPage(),
                'last_page' => $replies->lastPage(),
                'per_page' => $replies->perPage(),
                'total' => $replies->total(),
            ]
        ]);
    }

    public function getThread(Comment $comment)
    {
        $this->authorize('view', $comment);

        $comment->load(['user', 'allReplies.user']);

        return response()->json([
            'thread' => new CommentResource($comment)
        ]);
    }

    public function pin(Comment $comment) // Pin a comment to the top of its post you must be the post author
    {
        $post = $comment->post;

        $this->authorize('pin', $comment);

        Comment::where('post_id', $post->id)
            ->where('is_pinned', true)
            ->update(['is_pinned' => false]);

        $comment->update(['is_pinned' => true]);

        return response()->json([
            'message' => 'Comment pinned successfully',
            'comment' => new CommentResource($comment->fresh()->load('user'))
        ]);
    }

    public function unpin(Comment $comment)
    {
        $post = $comment->post;

        $this->authorize('unpin', $comment);

        $comment->update(['is_pinned' => false]);

        return response()->json([
            'message' => 'Comment unpinned successfully',
            'comment' => new CommentResource($comment->fresh()->load('user'))
        ]);
    }

    public function react(Request $request, Comment $comment)
    {
        $request->validate([
            'type' => 'required|string|max:50|in:like,sad,love,angry,wow,haha',
        ]);

        $user = Auth::user();
        $user->reaction($request->type, $comment);

        return response()->json([
            'message' => 'Reaction added successfully',
            'reaction' => $request->type,
            'reactions_count' => $comment->getReactionsWithCount()
        ]);
    }

    public function removeReaction(Comment $comment)
    {
        $user = Auth::user();
        $user->removeReactions($comment);

        return response()->json([
            'message' => 'Reaction removed successfully',
            'reactions_count' => $comment->getReactionsWithCount()
        ]);
    }

    /**
     * Get user's reaction on a comment
     */
    public function myReaction(Comment $comment)
    {
        $user = Auth::user();
        $reaction = $user->myReaction($comment);

        return response()->json([
            'reaction' => $reaction?->type ?? null
        ]);
    }

    public function getReactions(Comment $comment)
    {
        return response()->json([
            'comment_id' => $comment->id,
            'reactions' => $comment->getReactionsWithCount(),
            'reactors' => $comment->getReactors()
        ]);
    }

    public function countByPost($postId)
    {
        $count = Comment::where('post_id', $postId)->count();
        $topLevelCount = Comment::where('post_id', $postId)->topLevel()->count();

        return response()->json([
            'post_id' => (int)$postId,
            'total_comments' => $count,
            'top_level_comments' => $topLevelCount,
            'replies' => $count - $topLevelCount
        ]);
    }


    public function myRecentComments(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $comments = Comment::where('user_id', auth()->id())
            ->with(['post', 'user'])
            ->withCount('replies')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'comments' => CommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ]
        ]);
    }

    public function myCommentStats()
    {
        $userId = auth()->id();

        $totalComments = Comment::where('user_id', $userId)->count();
        $totalReplies = Comment::where('user_id', $userId)->whereNotNull('parent_id')->count();
        $totalTopLevel = $totalComments - $totalReplies;
        $pinnedComments = Comment::where('user_id', $userId)->where('is_pinned', true)->count();

        return response()->json([
            'total_comments' => $totalComments,
            'top_level_comments' => $totalTopLevel,
            'replies' => $totalReplies,
            'pinned_comments' => $pinnedComments,
        ]);
    }


    protected function notifyMentionedUsers(Comment $comment, User $mentionedBy): void
    {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $comment->content, $matches);

        if (empty($matches[1])) {
            return;
        }

        $usernames = array_unique($matches[1]);

        $mentionedUsers = User::whereIn('username', $usernames)
            ->where('id', '!=', $mentionedBy->id)
            ->get();

        foreach ($mentionedUsers as $user) {
            if ($user->isNotificationEnabled('mention')) {
                $user->notify(new MentionInCommentNotification($comment->load('post'), $mentionedBy));
               OneSignal::sendNotificationToUser(
                   $user->isNotificationEnabled('mention') ? 'You were mentioned in a comment by ' . $mentionedBy->name : 'You have a new notification',
                   $user->onesignal_player_id,
                   'deeplink://comments?id=' . $comment->id,
                   null,
                   null,null,
                   'Mentioned in a Comment'
               );
            }
        }
    }
}
