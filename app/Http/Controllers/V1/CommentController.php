<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\CommentsRequests\StoreCommentRequest;
use App\Http\Requests\CommentsRequests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Notifications\NewCommentNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Notification;

class CommentController
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Comment::class);
        $comments = Comment::with('post')->get();
        return response()->json([
            'comments' => new CommentResource($comments)
        ]);
    }

    public function store(StoreCommentRequest $request, Post $post)
    {
        $this->authorize('create', Comment::class);
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $validated['post_id'] = $post->id;

        $post = Post::with('user')->findOrFail($validated['post_id']);
        $comment = Comment::create($validated);

        Notification::send($post->user, new NewCommentNotification($comment));


        return response()->json([
            'message' => "Comment created successfully",
            'comment' => new CommentResource($comment)
        ], 201);
    }

    public function show(Comment $comment)
    {
        $this->authorize('view', $comment);
        return response()->json([
            'comment' => new CommentResource($comment)
        ]);
    }

    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        $this->authorize('update', $comment);
        $validated = $request->validated();
        $comment->update($validated);

        return response()->json([
            'message' => "Comment updated successfully",
            'comment' => new CommentResource($comment)
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

    public function getByPost($postId)
    {
        $this->authorize('viewAny', Comment::class);
        $comments = Comment::where('post_id', $postId)->with('user')->get();

        return response()->json([
            'comments' => CommentResource::collection($comments)
        ]);
    }

    public function getByUser($userId)
    {
        $this->authorize('viewAny', Comment::class);
        $comments = Comment::where('user_id', $userId)->with('post')->get();

        return response()->json([
            'comments' => CommentResource::collection($comments)
        ]);
    }

    public function reply(StoreCommentRequest $request, Comment $parentComment)
    {
        $this->authorize('create', Comment::class);
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $validated['parent_id'] = $parentComment->id;

        $comment = Comment::create($validated);

        return response()->json([
            'message' => "Reply created successfully",
            'comment' => new CommentResource($comment)
        ], 201);
    }
}
