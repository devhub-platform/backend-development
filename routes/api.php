<?php

use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\ForgetPasswordController;
use App\Http\Controllers\V1\Auth\SocialiteMediaController;
use App\Http\Controllers\V1\CodeEditorController;
use App\Http\Controllers\V1\CommentController;
use App\Http\Controllers\V1\FollowersController;
use App\Http\Controllers\V1\PostController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\ReactionController;
use App\Http\Controllers\V1\SavedPostController;
use App\Http\Controllers\V1\SearchController;
use App\Http\Controllers\V1\TagController;
use App\Http\Controllers\V1\UserRelationshipController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\Auth\VerifyEmailController;

Route::prefix('v1')->middleware('throttle:15,1')->group(function () {
    Route::controller(SocialiteMediaController::class)->group(function () {
        Route::get('auth/google/login', 'login');
        Route::get('auth/google/callback', 'callback');
        Route::get('auth/github/login', 'loginGithub');
        Route::get('auth/github/callback', 'callbackGithub');
        Route::get('auth/microsoft/login', 'loginMicrosoft');
        Route::get('auth/microsoft/callback', 'callbackMicrosoft');
    });

    Route::controller(AuthController::class)->middleware('guest')->group(function () {

        Route::post('login', 'login');
        Route::post('register', 'register');

        Route::middleware('auth:api')->group(function () {
            Route::post('logout', 'logout');
            Route::post('refresh', 'refreshToken');
            Route::post('me', 'user');
        });

        Route::controller(VerifyEmailController::class)->group(function () {
            Route::post('email/verify-otp', 'verifyEmailOtp');
            Route::post('email/resend-otp', 'resendEmail');
            Route::post('email/reset-otp', 'resetEmailVerification');
            Route::get('email/is-verified', 'isVerified');
        });

        Route::controller(ForgetPasswordController::class)->group(function () {
            Route::post('password/forgot', 'forgetPassword');
            Route::post('password/verify-otp', 'verifyOtp');
            Route::post('password/reset', 'resetPassword');
        });
    });

    Route::middleware(['auth:api', 'verified'])->group(function () {
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
        });
        Route::apiResource('posts', PostController::class);

        Route::controller(SearchController::class)->group(function () {
            Route::get('search/posts', 'searchPosts');
            Route::get('search/users', 'searchUsersByUsername');
            Route::get('search/tags', 'searchTagsName');
            Route::get('search/general', 'globalSearch');
            Route::get('search/histories', 'searchHistories');
            Route::delete('search/clear', 'clearSearch');
        });

        Route::controller(CommentController::class)->group(function () {
            Route::get('posts/{post}/comments', 'postComments');
            Route::get('users/{user}/comments', 'getByUser');
            Route::get('posts/{post}/comments', 'getByPost');
            Route::post('comments/{parentComment}/reply', 'reply');
            Route::post('posts/{post}/comments', 'store');
        });
        Route::apiResource('comments', CommentController::class);

        Route::controller(TagController::class)->group(function () {
            Route::get('tags/popular', 'popularTag');
            Route::get('tags', 'allTags');
            Route::post('tags', 'store');
        });
        Route::apiResource('tags', TagController::class);

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
        });

        Route::controller(FollowersController::class)->group(function () {
            Route::get('users/{user}/followers', 'followers');
            Route::get('users/{user}/followers/count', 'followersCount');
            Route::get('users/{user}/following', 'following');
            Route::get('users/{user}/following/count', 'followingCount');
            Route::post('users/{user}/follow', 'follow');
            Route::post('users/{user}/unfollow', 'unfollow');
        });

        Route::controller(ReactionController::class)->group(function () {
            Route::post('posts/{post}/react', 'reactToPost');
            Route::delete('posts/{post}/remove-react', 'removeReaction');
            Route::get('posts/{post}/reactors', 'getReactors');
            Route::get('posts/{post}/my-reaction', 'myReaction');
            Route::get('posts/{post}/reactions-count', 'reactionCounts');
        });

        Route::controller(SavedPostController::class)->group(function () {
            Route::get('saved-posts', 'index');
            Route::post('saved-posts/{post}', 'store');
            Route::delete('saved-posts/{post}', 'destroy');
        });

        Route::controller(NotificationController::class)->group(function () {
            Route::get('notifications', 'showNewCommentNotify');
            Route::post('notifications/mark-as-read', 'makeAllRead');
            Route::get('notifications/all', 'showAllNotifications');
            Route::delete('notifications/clear', 'clearAllNotifications');
            Route::post('notifications/{notification}/mark-as-read', 'makeAsRead');
            Route::get('notifications/reacts', 'showNewReactNotify');
        });

        Route::controller(CodeEditorController::class)->group(function () {
            Route::get('get-runtimes', 'getRuntimes');
            Route::post('execute-code', 'executeCode');
            Route::get('get-packages', 'getPackages');
            Route::post('install-packages', 'installPackages');
        });
    });
});

Route::fallback(function () {
    return response()->json([
        'message' => 'Resource not found. Please check the URL or API endpoint you are trying to access.'
    ], 404);
});
