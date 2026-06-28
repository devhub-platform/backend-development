<?php


use App\Http\Controllers\V1\CommentController;
use App\Http\Controllers\V1\PostController;
use App\Http\Controllers\V1\Posts\PostAIController;
use App\Http\Controllers\V1\PostViewController;
use App\Http\Controllers\V1\ReactionController;
use App\Http\Controllers\V1\SavedPostController;
use App\Http\Controllers\V1\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware('throttle:30,1')->group(function () {

        Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {

            // ─── Posts ────────────────────────────────────────────────────────────
            Route::controller(PostController::class)->group(function () {
                Route::get('user/posts', 'userPosts');
                Route::delete('posts/{post}/force', 'forceDelete');
                Route::post('posts/{post}/restore', 'restore');
                Route::get('posts/recent', 'recentPosts');
                Route::get('posts/top-views', 'topPostsViews');
                Route::get('posts/drafts', 'drafts');
                Route::get('posts/archives', 'archivesTrashed');
                Route::get('posts/report/reasons', 'reasonsToReport');
                Route::get('posts/{post}/tags', 'postsTags');
                Route::get('posts/{post}/tags-list', 'postsTagsList');
                Route::get('posts/{post}/comments', 'postComments');
                Route::post('posts/{post}/report', 'reportPost');
            });
            Route::apiResource('posts', PostController::class);

            Route::controller(PostViewController::class)->group(function () {
                Route::get('posts/viewed/recent', 'getRecentViewedPosts');
                Route::delete('posts/viewed/clear', 'clearViewedPosts');
            });

            // ─── Comments ─────────────────────────────────────────────────────────
            Route::middleware('blocked.user')->controller(CommentController::class)->group(function () {
                Route::post('posts/{post}/comments', 'store');
                Route::post('posts/{post}/comments/{parentComment}/reply', 'reply');
                Route::post('comments/{comment}/react', 'react');
                Route::delete('comments/{comment}/remove-react', 'removeReaction');
            });

            Route::controller(CommentController::class)->group(function () {
                Route::get('posts/{postId}/comments', 'getByPost');
                Route::get('posts/{postId}/comments/count', 'countByPost');
                Route::get('users/{userId}/comments', 'getByUser');
                Route::get('comments/{comment}/replies', 'getReplies');
                Route::get('comments/{comment}/thread', 'getThread');
                Route::post('comments/{comment}/pin', 'pin');
                Route::post('comments/{comment}/unpin', 'unpin');
                Route::get('comments/{comment}/my-reaction', 'myReaction');
                Route::get('comments/{comment}/reactions', 'getReactions');
                Route::delete('comments/{id}/force', 'forceDelete');
                Route::get('my/comments', 'myRecentComments');
                Route::get('my/comments/stats', 'myCommentStats');
            });


            // ─── Saved Posts ──────────────────────────────────────────────────────
            Route::controller(SavedPostController::class)->group(function () {
                Route::get('saved-posts', 'index');
                Route::post('saved-posts/{post}', 'store');
                Route::delete('saved-posts/{post}', 'destroy');
            });

            // ─── Reactions ────────────────────────────────────────────────────────
            Route::middleware('blocked.user')->controller(ReactionController::class)->group(function () {
                Route::post('posts/{post}/react', 'reactToPost');
                Route::delete('posts/{post}/remove-react', 'removeReaction');
            });

            Route::controller(ReactionController::class)->group(function () {
                Route::get('user/posts/total-reactions', 'getTotalReactionsOnPosts');
                Route::get('posts/{post}/reactors', 'getReactors');
                Route::get('posts/{post}/my-reaction', 'myReaction');
                Route::get('posts/{post}/reactions-count', 'reactionCounts');
            });


            Route::controller(SearchController::class)->group(function () {
                Route::get('search/posts', 'searchPosts');
                Route::get('search/users', 'searchUsersByUsername');
                Route::get('search/tags', 'searchTags');
                Route::get('search/histories', 'getSearchHistory');
                Route::delete('search/clear', 'clearSearchHistory');
            });

        });
    });
});

