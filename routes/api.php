<?php

use App\Http\Controllers\V1\AiModels\AIChatController;
use App\Http\Controllers\V1\AiModels\HistoryController;
use App\Http\Controllers\V1\AiModels\PostSummarizeController;
use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\ForgetPasswordController;
use App\Http\Controllers\V1\Auth\SocialiteMediaController;
use App\Http\Controllers\V1\Auth\VerifyEmailController;
use App\Http\Controllers\V1\CodeEditorController;
use App\Http\Controllers\V1\CommentController;
use App\Http\Controllers\V1\FollowersController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\PostController;
use App\Http\Controllers\V1\PostViewController;
use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\ReactionController;
use App\Http\Controllers\V1\ReadingListController;
use App\Http\Controllers\V1\ReportController;
use App\Http\Controllers\V1\SavedPostController;
use App\Http\Controllers\V1\SearchController;
use App\Http\Controllers\V1\SettingController;
use App\Http\Controllers\V1\TagController;
use App\Http\Controllers\V1\TagFollowController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\V1\UserStatusesController;
use App\Http\Controllers\V1\VerifyAltEmailController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\AiModels\AttachmentController;

Route::prefix('v1')->middleware('throttle:15,1')->group(function () {

    Route::controller(SocialiteMediaController::class)->group(function () {
        Route::post('auth/google/login', 'loginGoogle');
        Route::get('auth/google/callback', 'callbackGoogle');
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

    Route::middleware(['auth:api', 'throttle:25,1'])->group(function () {

        Route::controller(AuthController::class)->group(function () {
            Route::post('logout', 'logout');
            Route::post('refresh', 'refreshToken');
            Route::get('me', 'user');
        });

        Route::controller(PostController::class)->group(function () {
            Route::get('user/posts', 'userPosts');
            Route::delete('posts/{post}/force', 'forceDelete');
            Route::post('posts/{id}/restore', 'restore');
            Route::get('posts/recent', 'recentPosts');
            Route::get('posts/top-views', 'topPostsViews');
            Route::get('posts/drafts', 'drafts');
            Route::get('posts/archives', 'archivesTrashed');
            Route::get('posts/report/reasons', 'reasonsToReport');
            Route::get('posts/{post}/tags', 'postsTags');
            Route::get('posts/{post}/tags-list', 'postsTagsList');
            Route::get('posts/{post}/comments', 'postComments');
            Route::post('posts/{post}/report', 'reportPost');
            Route::post('posts/{post}/restore', 'restore');
            Route::delete('posts/{post}/force', 'forceDelete');
        });
        Route::apiResource('posts', PostController::class);

        Route::controller(PostViewController::class)->group(function () {
            Route::get('posts/viewed/recent', 'getRecentViewedPosts');
            Route::delete('posts/viewed/clear', 'clearViewedPosts');
        });

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

        Route::controller(SearchController::class)->group(function () {
            Route::get('search/posts', 'searchPosts');
            Route::get('search/users', 'searchUsersByUsername');
            Route::get('search/tags', 'searchTags');
            Route::get('search/histories', 'getSearchHistory');
            Route::delete('search/clear', 'clearSearchHistory');
        });

        // Comments
        Route::controller(CommentController::class)->group(function () {
            // Create & Reply
            Route::post('posts/{post}/comments', 'store');
            Route::post('posts/{post}/comments/{parentComment}/reply', 'reply');

            // Get comments by post/user
            Route::get('posts/{postId}/comments', 'getByPost');
            Route::get('posts/{postId}/comments/count', 'countByPost');
            Route::get('users/{userId}/comments', 'getByUser');

            // Replies & Thread
            Route::get('comments/{comment}/replies', 'getReplies');
            Route::get('comments/{comment}/thread', 'getThread');

            // Pin/Unpin
            Route::post('comments/{comment}/pin', 'pin');
            Route::post('comments/{comment}/unpin', 'unpin');

            // Reactions
            Route::post('comments/{comment}/react', 'react');
            Route::delete('comments/{comment}/remove-react', 'removeReaction');
            Route::get('comments/{comment}/my-reaction', 'myReaction');
            Route::get('comments/{comment}/reactions', 'getReactions');

            // Soft delete & Restore
            Route::delete('comments/{id}/force', 'forceDelete');

            // My comments
            Route::get('my/comments', 'myRecentComments');
            Route::get('my/comments/stats', 'myCommentStats');
        });
//        Route::apiResource('comments', CommentController::class);


        Route::controller(TagController::class)->group(function () {
            Route::get('tags/popular', 'popularTag');
            Route::get('tags', 'allTags');
            Route::post('tags', 'store');
            Route::post('posts/{post}/tags', 'attachTagsToPost');
            Route::delete('posts/{post}/tags/{tag}', 'detachTagFromPost');
        });

        // Profile
        Route::controller(ProfileController::class)->group(function () {
            Route::get('profile', 'show');
            Route::patch('profile', 'update');
            Route::get('profile/user/posts', 'userPosts');
            Route::get('profile/user/comments', 'userComments');
            Route::get('profile/user/tags', 'userTags');
            Route::post('profile/upload/avatar', 'uploadAvatarImage');
            Route::post('profile/upload/cover-image', 'uploadCoverImage');
            Route::get('profile/activity', 'activity');
            Route::get('profile/details', 'details');
        });

        // Followers
        Route::controller(FollowersController::class)->group(function () {
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

        // Saved Posts (Reading List)
        Route::controller(SavedPostController::class)->group(function () {
            Route::get('saved-posts', 'index');
            Route::post('saved-posts/{post}', 'store');
            Route::delete('saved-posts/{post}', 'destroy');
        });

        // Notifications
        Route::controller(NotificationController::class)->group(function () {
            Route::get('notifications/comments', 'showNewCommentNotify');
            Route::get('notifications/all', 'showAllNotifications');
            Route::get('notifications/reacts', 'showNewReactNotify');

            Route::get('notifications/new-followers', 'showNewFollowersNotifications');
            Route::delete('notifications/followers/clear', 'clearAllNotificationFromFollowers');

            Route::get('notifications/post-created', 'newPostCreateFromFollower');

            Route::get('notifications/mention', 'showNewMentionNotifications');

            Route::post('notifications/mark-as-read', 'makeAllRead');
            Route::post('notifications/{notification}/mark-as-read', 'makeAsRead');
            Route::delete('notifications/clear', 'clearAllNotifications');

            Route::get('notifications/preferences', 'getNotificationPreferences');
            Route::put('notifications/preferences', 'updateNotificationPreferences');
            Route::patch('notifications/preferences/{type}/toggle', 'toggleNotificationPreference');
        });

        // Tags Follow
        Route::controller(TagFollowController::class)->group(function () {
            Route::post('tags/{tag}/follow', 'follow');
            Route::delete('tags/{tag}/unfollow', 'unfollow');
            Route::get('tags/{tag}/followers', 'listFollowing');
        });

        // User Statuses
        Route::controller(UserStatusesController::class)->group(function () {
            Route::get('user/statuses', 'getStatuses');
            Route::post('user/statuses', 'store');
            Route::patch('user/statuses', 'update');
            Route::delete('user/statuses', 'delete');
            Route::post('user/statuses/set-busy', 'setBusy');
            Route::post('user/statuses/set-available', 'setAvailable');
            Route::post('user/statuses/clear-expired', 'clearExpiredStatuses');
            Route::get('users/{username}/status', 'getUserStatus');
        });

        // Reading Lists
        Route::controller(ReadingListController::class)->group(function () {
            Route::get('reading-lists/lists/posts', 'index');
            Route::post('reading-lists', 'store');
            Route::get('reading-lists/{readingList}', 'show');
            Route::patch('reading-lists/{readingList}', 'update');
            Route::delete('reading-lists/{readingList}', 'destroy');
            Route::post('reading-lists/{readingList}/add-post/{post}', 'addPostToReadingList');
            Route::delete('reading-lists/{readingList}/remove-post/{post}', 'removePostFromReadingList');
            Route::post('reading-lists/{readingList}/move-post/{post}', 'movePostToAnotherList');
            Route::post('reading-lists/{readingList}/add-note/{post}', 'addNoteToPostInReadingList');
            Route::delete('reading-lists/{readingList}/delete-note/{post}', 'deleteNoteInPostInReadingList');
            Route::post('reading-lists/{readingList}/duplicate', 'duplicateReadingList');
            Route::get('reading-lists/{readingList}/show-notes/{post}', 'showNotesInReadingList');
        });

        // Code Editor
        Route::controller(CodeEditorController::class)->group(function () {
            Route::get('code/runtimes', 'runtimes');
            Route::post('code/execute', 'execute');
            Route::get('code/search-runtimes', 'searchInRuntimes');
            Route::get('code/languages', 'languages');
        });

        Route::controller(PostSummarizeController::class)->group(function () {
            Route::post('ai/summarize/post/{post}', 'summarizePost');
            Route::get('ai/summarize/post/languages', 'getSupportedLanguages');
            Route::post('ai/summarize/llama/post/{post}', 'summarizePostUsingLlama');
            Route::post('ai/translate/post/{post}', 'translatePost');
            Route::post('ai/analyze/post/{post}', 'analyzePost');
            Route::post('ai/question/post/{post}', 'answerQuestionAboutPost');
            Route::post('ai/generate/content', 'generateContent');
        });

        // Reports & Blocking
        Route::controller(ReportController::class)->group(function () {
            Route::post('reports/block/{target}', 'block');
            Route::post('reports/report/{target}', 'report');
            Route::post('reports/unblock/{target}', 'unblock');
            Route::get('reports/blocked-users', 'blockList');
        });

        // Settings
        Route::controller(SettingController::class)->group(function () {
            Route::patch('settings/update-password', 'updatePassword');
            Route::post('settings/social-accounts', 'addSocialAccounts');
            Route::delete('settings/soft/delete-account', 'delete');
            Route::delete('settings/force/delete-account', 'forceDelete');
        });

        Route::controller(VerifyAltEmailController::class)->group(function () {
            Route::post('settings/alt-email/send-otp', 'addAltEmail');
            Route::post('settings/alt-email/verify-otp', 'verifyAltEmail');
            Route::delete('settings/alt-email/remove', 'removeAltEmail');
        });

        Route::prefix('ai-chat')->group(function () {
            Route::controller(AIChatController::class)->group(function () {
                Route::post('send', 'chat');
                Route::get('models', 'models');
            });
            Route::post('attachments/upload', [AttachmentController::class, 'upload']);

            Route::prefix('history')->controller(HistoryController::class)->group(function () {
                Route::get('sessions', 'sessions');
                Route::post('sessions/create', 'create');
                Route::get('sessions/{sessionId}', 'show');
                Route::delete('sessions/{sessionId}', 'delete');
                Route::post('sessions/{sessionId}/pin', 'pin');
                Route::post('sessions/{sessionId}/unpin', 'unpin');
                Route::post('sessions/{sessionId}/close', 'close');
                Route::post('sessions/{sessionId}/activate', 'activate');
                Route::put('sessions/{sessionId}/title', 'updateTitle');
            });
        });
    });
});

// Fallback & Welcome Routes
Route::fallback(function () {
    return response()->json([
        'message' => 'Resource not found , The API endpoint does not exist , visit document.',
        'documentation' => 'https://0yviq6a5i5.apidog.io/',
        'version' => 'API v1 Devhub is a platform for developers to share knowledge, collaborate on projects, and connect with other developers. Our API allows you to access our platform programmatically, enabling you to integrate our features into your applications and services.',
    ], 404);
});
