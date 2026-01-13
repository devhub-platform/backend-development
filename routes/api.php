<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\ForgetPasswordController;
use App\Http\Controllers\V1\Auth\SocialiteMediaController;
use App\Http\Controllers\V1\Auth\VerifyEmailController;

// Main controllers
use App\Http\Controllers\V1\PostController;
use App\Http\Controllers\V1\CommentController;
use App\Http\Controllers\V1\TagController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\FollowersController;
use App\Http\Controllers\V1\ReactionController;
use App\Http\Controllers\V1\SavedPostController;
use App\Http\Controllers\V1\SearchController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\CodeEditorController;
use App\Http\Controllers\V1\TagFollowController;
use App\Http\Controllers\V1\UserStatusesController;
use App\Http\Controllers\V1\ReadingListController;
use App\Http\Controllers\V1\AiModels\LlamaController;
use App\Http\Controllers\V1\AiModels\AIChatController;

// v1 API group with throttle
Route::prefix('v1')->middleware('throttle:15,1')->group(function () {

    // Socialite login
    Route::controller(SocialiteMediaController::class)->group(function () {
        Route::get('auth/google/login', 'login');
        Route::get('auth/google/callback', 'callback');
        Route::get('auth/github/login', 'loginGithub');
        Route::get('auth/github/callback', 'callbackGithub');
    });

    // Auth routes
    Route::controller(AuthController::class)->group(function () {
        Route::post('login', 'login');
        Route::post('register', 'register');

        Route::middleware('auth:api')->group(function () {
            Route::post('logout', 'logout');
            Route::post('refresh', 'refreshToken');
            Route::get('me', 'user');
        });

        // Email verification
        Route::controller(VerifyEmailController::class)->group(function () {
            Route::post('email/verify-otp', 'verifyEmailOtp');
            Route::post('email/send-otp', 'sendEmailOTP');
            Route::get('email/is-verified', 'isVerified');
        });

        // Password reset
        Route::controller(ForgetPasswordController::class)->group(function () {
            Route::post('password/forgot', 'forgetPassword');
            Route::post('password/verify-otp', 'verifyOtp');
            Route::post('password/reset', 'resetPassword');
        });
    });

    // Protected routes (auth + verified + throttle)
    Route::middleware(['auth:api', 'verified', 'throttle:15,1'])->group(function () {

        // Posts
        Route::controller(PostController::class)->group(function () {
            Route::get('user/posts', 'userPosts');
            Route::get('search/posts', 'search');
            Route::delete('posts/{post}/force', 'forceDelete');
            Route::post('posts/{id}/restore', 'restore');
            Route::get('posts/recent', 'recentPosts');
            Route::get('posts/tags', 'postsTags');
            Route::get('posts/{post}/tags', 'postsTags');
            Route::post('posts/{post}/tags', 'attachTags');
            Route::delete('posts/{post}/tags/{tag}', 'detachTag');
            Route::get('posts/{post}/tags-list', 'postsTagsList');
            Route::get('posts/{post}/comments', 'postComments');
            Route::get('posts/drafts', 'drafts');
            Route::get('posts/archives', 'archivesTrashed');
            Route::get('posts/generate/cover-image', 'generateCoverImage');
            Route::post('posts/upload/cover-image', 'uploadPostCover');
            Route::post('posts/upload/image', 'uploadPostImage');
            Route::post('posts/{post}/summarize', 'summarizationPost');
        });
        Route::apiResource('posts', PostController::class);

        // Search
        Route::controller(SearchController::class)->group(function () {
            Route::get('search/posts', 'searchPosts');
            Route::get('search/users', 'searchUsersByUsername');
            Route::get('search/tags', 'searchTagsName');
            Route::get('search/histories', 'searchHistories');
            Route::delete('search/clear', 'clearSearch');
        });

        // Comments
        Route::controller(CommentController::class)->group(function () {
            Route::get('posts/{post}/comments', 'postComments');
            Route::get('users/{user}/comments', 'getByUser');
            Route::get('posts/{post}/comments', 'getByPost');
            Route::post('comments/{parentComment}/reply', 'reply');
            Route::post('posts/{post}/comments', 'store');
        });
        Route::apiResource('comments', CommentController::class);

        // Tags
        Route::controller(TagController::class)->group(function () {
            Route::get('tags/popular', 'popularTag');
            Route::get('tags', 'allTags');
            Route::post('tags', 'store');
        });
        Route::apiResource('tags', TagController::class);

        // Profile
        Route::controller(ProfileController::class)->group(function () {
            Route::get('profile', 'show');
            Route::patch('profile', 'update');
            Route::delete('profile', 'delete');
            Route::delete('profile/force', 'forceDelete');
            Route::get('profile/user/posts', 'userPosts');
            Route::get('profile/user/comments', 'userComments');
            Route::get('profile/user/tags', 'userTags');
            Route::post('profile/upload/avatar', 'uploadAvatar');
            Route::post('profile/upload/cover-image', 'uploadCoverImage');
            Route::post('profile/update-password', 'updatePassword');
            Route::get('profile/activity', 'activity');
            Route::get('profile/details', 'details');
            Route::post('profile/add-social-link', 'addSocialAccounts');
        });

        // Followers
        Route::controller(FollowersController::class)->group(function () {
            Route::get('users/{user}/followers', 'usersFollowers');
            Route::get('users/{user}/followers/count', 'followersCount');
            Route::get('users/{user}/following', 'usersFollowing');
            Route::get('users/{user}/following/count', 'followingCount');
            Route::post('users/{user}/follow', 'follow');
            Route::post('users/{user}/unfollow', 'unfollow');
            Route::get('followers/suggestions', 'suggestions');
            Route::get('followers/my-followers', 'myFollowers');
            Route::get('followers/my-following', 'myFollowing');
        });

        // Reactions
        Route::controller(ReactionController::class)->group(function () {
            Route::get('user/posts/total-reactions', 'getTotalReactionsOnPosts');
            Route::post('posts/{post}/react', 'reactToPost');
            Route::delete('posts/{post}/remove-react', 'removeReaction');
            Route::get('posts/{post}/reactors', 'getReactors');
            Route::get('posts/{post}/my-reaction', 'myReaction');
            Route::get('posts/{post}/reactions-count', 'reactionCounts');
        });

        // Saved posts
        Route::controller(SavedPostController::class)->group(function () {
            Route::get('saved-posts', 'index');
            Route::post('saved-posts/{post}', 'store');
            Route::delete('saved-posts/{post}', 'destroy');
        });

        // Notifications
        Route::controller(NotificationController::class)->group(function () {
            Route::get('notifications', 'showNewCommentNotify');
            Route::post('notifications/mark-as-read', 'makeAllRead');
            Route::get('notifications/all', 'showAllNotifications');
            Route::delete('notifications/clear', 'clearAllNotifications');
            Route::post('notifications/{notification}/mark-as-read', 'makeAsRead');
            Route::get('notifications/reacts', 'showNewReactNotify');
        });

        // Code editor
        Route::controller(CodeEditorController::class)->group(function () {
            Route::get('/code/runtimes', 'runtimes');
            Route::post('/code/execute', 'execute');
        });

        // Tag follow
        Route::controller(TagFollowController::class)->group(function () {
            Route::post('tags/{tag}/follow', 'follow');
            Route::delete('tags/{tag}/unfollow', 'unfollow');
            Route::get('tags/{tag}/followers', 'listFollowing');
        });

        // User statuses
        Route::controller(UserStatusesController::class)->group(function () {
            Route::get('user/statuses', 'getStatuses');
            Route::post('user/statuses', 'store');
            Route::delete('user/statuses', 'delete');
        });

        // Reading lists
        Route::controller(ReadingListController::class)->group(function () {
            Route::get('reading-lists/lists/posts', 'index');
            Route::post('reading-lists', 'store');
            Route::get('reading-lists/{readingList}', 'show');
            Route::get('reading-lists/', 'Lists');
            Route::patch('reading-lists/{readingList}', 'update');
            Route::delete('reading-lists/{readingList}', 'destroy');
            Route::post('reading-lists/{readingList}/add-post/{post}', 'addPostToReadingList');
            Route::delete('reading-lists/{readingList}/remove-post/{post}', 'removePostFromReadingList');
            Route::post('reading-lists/{readingList}/move-post/{post}', 'movePostToAnotherList');
            Route::post('reading-lists/{readingList}/add-note/{post}', 'addNoteToPostInReadingList');
            Route::delete('reading-lists/{readingList}/delete-note/{post}', 'deleteNoteInPostInReadingList');
            Route::get('reading-lists/{readingList}/show-notes/{post}', 'showNotesInReadingList');
        });

        // Llama AI
        Route::controller(LlamaController::class)->group(function () {
            Route::post('ai/llama/generate-text', 'sendAiRequest');
        });

    });

});

Route::prefix('v1')->group(function () {

    // AI Chat routes
    Route::prefix('ai-chat')->group(function () {
        Route::post('/', [AIChatController::class, 'chat']); // send message
        Route::get('/models', [AIChatController::class, 'models']); // list models
    });

});
// Fallback
Route::fallback(function () {
    return response()->json([
        'message' => 'Resource not found. Please check the URL or API endpoint you are trying to access.'
    ], 404);
});
