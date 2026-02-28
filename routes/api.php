<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\ForgetPasswordController;
use App\Http\Controllers\V1\Auth\SocialiteMediaController;
use App\Http\Controllers\V1\Auth\VerifyEmailController;

use App\Http\Controllers\V1\PostController;
use App\Http\Controllers\V1\PostViewController;
use App\Http\Controllers\V1\CommentController;
use App\Http\Controllers\V1\ReactionController;
use App\Http\Controllers\V1\SavedPostController;
use App\Http\Controllers\V1\TagController;
use App\Http\Controllers\V1\TagFollowController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\FollowersController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\ReadingListController;
use App\Http\Controllers\V1\ReportController;
use App\Http\Controllers\V1\SearchController;
use App\Http\Controllers\V1\SettingController;
use App\Http\Controllers\V1\UserStatusesController;
use App\Http\Controllers\V1\VerifyAltEmailController;
use App\Http\Controllers\V1\CodeEditorController;

use App\Http\Controllers\V1\Chats\ChatController;
use App\Http\Controllers\V1\Chats\MessageController;

use App\Http\Controllers\V1\AiModels\AIChatController;
use App\Http\Controllers\V1\AiModels\AttachmentController;
use App\Http\Controllers\V1\AiModels\HistoryController;
use App\Http\Controllers\V1\AiModels\PostChatController;
use App\Http\Controllers\V1\AiModels\QuestionChatController;

use App\Http\Controllers\V1\QuestionController;
use App\Http\Controllers\V1\AnswerController;

Route::prefix('v1')->middleware('throttle:15,1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes
    |--------------------------------------------------------------------------
    */

    Route::controller(SocialiteMediaController::class)->group(function () {
        Route::post('auth/google/login', 'loginGoogle');
        Route::get('auth/google/callback', 'callbackGoogle');
        Route::post('auth/google/login-mobile', 'loginGoogleForMobile');
        Route::post('auth/github/login', 'loginGithub');
        Route::get('auth/github/callback', 'callbackGithub');
    });

    Route::controller(AuthController::class)->group(function () {
        Route::post('login', 'login');
        Route::post('register', 'register');
    });

    Route::controller(VerifyEmailController::class)->group(function () {
        Route::post('email/verify-otp', 'verifyEmailOtp');
        Route::post('email/send-otp', 'sendEmailOTP');
        Route::get('email/is-verified', 'isVerified');
    });

    Route::controller(ForgetPasswordController::class)->group(function () {
        Route::post('password/forgot', 'forgetPassword');
        Route::post('password/verify-otp', 'verifyOtp');
        Route::post('password/reset', 'resetPassword');
    });

    Route::get('ai-chat/models', [AIChatController::class, 'models']);

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth:api', 'throttle:25,1'])->group(function () {

        // Auth
        Route::controller(AuthController::class)->group(function () {
            Route::post('logout', 'logout');
            Route::post('refresh', 'refreshToken');
            Route::get('me', 'user');
        });

        // Posts
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
        Route::post('posts/{post}/ai-chat', [PostChatController::class, 'chat']);

        // Post Views
        Route::controller(PostViewController::class)->group(function () {
            Route::get('posts/viewed/recent', 'getRecentViewedPosts');
            Route::delete('posts/viewed/clear', 'clearViewedPosts');
        });

        // Q&A
        Route::controller(QuestionController::class)->group(function () {
            Route::get('questions', 'index');
            Route::post('questions/create', 'store');
            Route::get('questions/hot', 'trending');
            Route::get('questions/{question}', 'show');
            Route::put('questions/{question}', 'update');
            Route::delete('questions/{question}', 'destroy');
            Route::post('questions/{question}/vote', 'vote');
        });

        Route::controller(AnswerController::class)->group(function () {
            Route::get('questions/{question}/answers', 'index');
            Route::post('questions/{question}/answers/create', 'store');
            Route::get('questions/{question}/answers/{answer}', 'show');
            Route::put('questions/{question}/answers/{answer}', 'update');
            Route::delete('questions/{question}/answers/{answer}', 'destroy');
            Route::post('questions/{question}/answers/{answer}/vote', 'vote');
            Route::post('questions/{question}/answers/{answer}/accept', 'accept');
            Route::post('questions/{question}/answers/{answer}/unaccept', 'unaccept');
        });

        Route::post('questions/{question}/ai-chat', [QuestionChatController::class, 'chat']);

        // Users
        Route::controller(UserController::class)->group(function () {
            Route::get('users', 'index');
            Route::get('users/recommended', 'getRecommendedUsers');
            Route::get('users/{id}', 'showUserProfile');
            Route::get('users/{id}/similar-skills', 'getUsersWithSimilarSkills');
            Route::get('users/{user}/posts', 'userPosts');
            Route::get('users/{user}/comments', 'userComments');
            Route::get('users/{user}/tags', 'userTags');
            Route::get('users/{user}/followers', 'usersFollowers');
            Route::get('users/{user}/followers/count', 'usersFollowersCount');
            Route::get('users/{user}/following', 'usersFollowing');
            Route::get('users/{user}/mutual-followers', 'getMutualFollowers');
            Route::get('users/{user}/mutual-following', 'checkMutualFollowing');
        });

        // Chat
        Route::prefix('chat')->controller(ChatController::class)->group(function () {
            Route::get('conversations', 'index');
            Route::post('conversations', 'createOrGetConversation');
            Route::get('conversations/{conversation}', 'show');
            Route::delete('conversations/{conversation}', 'destroy');
            Route::get('conversations/{conversation}/messages', 'getMessages');
            Route::post('conversations/{conversation}/messages', 'sendMessage');
            Route::delete('conversations/{conversation}/messages/{messageId}', 'deleteMessage');
            Route::post('conversations/{conversation}/messages/read', 'markAsRead');
            Route::get('unread-count', 'unreadCount');
        });

        Route::prefix('messages')->controller(MessageController::class)->group(function () {
            Route::post('{conversation}/send', 'sendMessage');
            Route::post('{conversation}/send-attachment', 'sendMessageWithAttachment');
            Route::delete('{conversation}/{messageId}', 'deleteMessage');
            Route::post('{conversation}/mark-as-read', 'markAsRead');
            Route::put('{conversation}/{messageId}', 'updateMessage');
            Route::post('{conversation}/{messageId}/reaction', 'addReactionToMessage');
            Route::post('{conversation}/{messageId}/flag', 'makeMessageAsFlagged');
        });

        // AI Chat
        Route::prefix('ai-chat')->group(function () {

            Route::post('send', [AIChatController::class, 'chat']);

            Route::controller(AttachmentController::class)
                ->prefix('attachments')
                ->middleware('throttle:10,1')
                ->group(function () {
                    Route::post('upload', 'upload');
                    Route::delete('{attachmentId}', 'destroy');
                    Route::get('{attachmentId}/status', 'status');
                });

            Route::prefix('sessions')->controller(HistoryController::class)->group(function () {
                Route::get('/', 'sessions');
                Route::post('create', 'create');
                Route::get('{sessionId}', 'show');
                Route::delete('{sessionId}', 'delete');
                Route::post('{sessionId}/pin', 'pin');
                Route::post('{sessionId}/unpin', 'unpin');
                Route::put('{sessionId}/title', 'updateTitle');
            });
        });
    });
});

Route::fallback(function () {
    return response()->json([
        'Hey_there!' => 'Ramadan Mubarak!!',
        'message' => 'Resource not found, the API endpoint does not exist',
        'documentation' => 'https://0yviq6a5i5.apidog.io/',
        'version' => 'API v1 - Devhub platform',
    ], 404);
});
