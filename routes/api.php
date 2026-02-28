<?php

use App\Http\Controllers\V1\AiModels\AIChatController;
use App\Http\Controllers\V1\Chats\ChatController;
use App\Http\Controllers\V1\AiModels\AttachmentController;
use App\Http\Controllers\V1\AiModels\HistoryController;
use App\Http\Controllers\V1\AiModels\PostChatController;
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
use App\Http\Controllers\V1\Chats\MessageController;

//$chatRoutesPrefix = config('musonza_chat.routes.path_prefix', 'chat');
//$middleware       = config('musonza_chat.routes.middleware', ['web']);

Route::prefix('v1')->middleware('throttle:15,1')->group(function () {

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

        Route::post('posts/{post}/ai-chat', [PostChatController::class, 'chat']);

        Route::controller(PostViewController::class)->group(function () {
            Route::get('posts/viewed/recent', 'getRecentViewedPosts');
            Route::delete('posts/viewed/clear', 'clearViewedPosts');
        });

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

        Route::controller(TagController::class)->group(function () {
            Route::get('tags/popular', 'popularTag');
            Route::get('tags', 'allTags');
            Route::post('tags', 'store');
            Route::post('posts/{post}/tags', 'attachTagsToPost');
            Route::delete('posts/{post}/tags/{tag}', 'detachTagFromPost');
        });

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

        Route::middleware('blocked.user')->controller(FollowersController::class)->group(function () {
            Route::post('users/{user}/follow', 'follow');
            Route::post('users/{user}/unfollow', 'unfollow');
        });

        Route::controller(FollowersController::class)->group(function () {
            Route::get('users/{user}/follow-stats/count', 'UserFollowStats');
            Route::get('followers/suggestions', 'suggestions');
            Route::get('followers/my-followers', 'myFollowers');
            Route::get('followers/my-following', 'myFollowing');
        });

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

        Route::controller(SavedPostController::class)->group(function () {
            Route::get('saved-posts', 'index');
            Route::post('saved-posts/{post}', 'store');
            Route::delete('saved-posts/{post}', 'destroy');
        });

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

        Route::controller(TagFollowController::class)->group(function () {
            Route::post('tags/{tag}/follow', 'follow');
            Route::delete('tags/{tag}/unfollow', 'unfollow');
            Route::get('tags/{tag}/followers', 'listFollowing');
        });

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

        Route::controller(ReadingListController::class)->group(function () {
            Route::get('reading-lists/lists/posts', 'index');
            Route::post('reading-lists', 'store');
            Route::get('reading-lists/{readingList}', 'show');
            Route::patch('reading-lists/{readingList}', 'update');
            Route::delete('reading-lists/{readingList}', 'destroy');
            Route::delete('reading-lists/{readingList}/remove-post/{post}', 'removePostFromReadingList');
            Route::post('reading-lists/{readingList}/duplicate', 'duplicateReadingList');
        });

        Route::middleware('blocked.user')->controller(ReadingListController::class)->group(function () {
            Route::post('reading-lists/{readingList}/add-post/{post}', 'addPostToReadingList');
            Route::post('reading-lists/{readingList}/move-post/{post}', 'movePostToAnotherList');
            Route::post('reading-lists/{readingList}/add-note/{post}', 'addNoteToPostInReadingList');
            Route::delete('reading-lists/{readingList}/delete-note/{post}', 'deleteNoteInPostInReadingList');
            Route::get('reading-lists/{readingList}/show-notes/{post}', 'showNotesInReadingList');
        });

        Route::controller(CodeEditorController::class)->group(function () {
            Route::get('code/runtimes', 'runtimes');
            Route::post('code/execute', 'execute');
            Route::get('code/search-runtimes', 'searchInRuntimes');
            Route::get('code/languages', 'languages');
        });

        Route::controller(ReportController::class)->group(function () {
            Route::post('reports/block/{target}', 'block');
            Route::post('reports/report/{target}', 'report');
            Route::post('reports/unblock/{target}', 'unblock');
            Route::get('reports/blocked-users', 'blockList');
            Route::get('reports/reasons', 'reason');
        });

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

        Route::prefix('chat')->controller(ChatController::class)->group(function () {

            Route::get('conversations', 'index');
            Route::post('conversations', 'createOrGetConversation');
            Route::get('conversations/{conversation}', 'show');
            Route::delete('conversations/{conversation}', 'destroy');

            Route::get('conversations/{conversation}/messages', 'getMessages');
            Route::post('conversations/{conversation}/messages', 'sendMessage');
            Route::delete('conversations/{conversation}/messages/{messageId}', 'deleteMessage');
            Route::post('conversations/{conversation}/messages/read', 'markAsRead');
            Route::delete('conversations/{conversation}/clear', 'clearConversations');

            Route::get('unread-count', 'unreadCount');
        });

        Route::prefix('messages')->controller(MessageController::class)->group(function () {
            Route::post('{conversation}/send', 'sendMessage');
            Route::post('{conversation}/send-attachment', 'sendMessageWithAttachment');
            Route::delete('{messageId}/conversation/{conversation}', 'deleteMessage');
            Route::post('{conversation}/mark-as-read', 'markAsRead');
            Route::put('{conversation}/{messageId}', 'updateMessage');
            Route::post('{conversation}/{messageId}/reaction', 'reactToMessage');
            Route::post('{conversation}/{messageId}/toggle-reaction', 'toggleReaction');
            Route::delete('{conversation}/{messageId}/reaction', 'unreactToMessage');
            Route::get('{conversation}/{messageId}/reactions-summary', 'getReactionsSummary');
            Route::post('{conversation}/{messageId}/flag', 'makeMessageAsFlagged');
        });

        Route::prefix('ai-chat')->group(function () {
            Route::post('send', [AIChatController::class, 'chat']);

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

//        $chatRoutesPrefix = config('musonza_chat.routes.path_prefix');
//        $middleware = config('musonza_chat.routes.middleware');
//
//        Route::group([
//            'middleware' => $middleware ?? ['auth:api'],
//            'namespace' => 'Musonza\Chat\Http\Controllers',
//            'prefix' => $chatRoutesPrefix,
//        ], function () {
//            /* Conversation */
//            Route::get('/conversations', 'ConversationController@index')->name('conversations.index');
//            Route::post('/conversations', 'ConversationController@store')->name('conversations.store');
//            Route::get('/conversations/{id}', 'ConversationController@show')->name('conversations.show');
//            Route::put('/conversations/{id}', 'ConversationController@update')->name('conversations.update');
//            Route::delete('/conversations/{id}', 'ConversationController@destroy')->name('conversations.destroy');
//
//            /* Conversation Participation */
//            Route::post('/conversations/{id}/participants', 'ConversationParticipationController@store')
//                ->name('conversations.participation.store');
//            Route::delete('/conversations/{id}/participants/{participation_id}', 'ConversationParticipationController@destroy')
//                ->name('conversations.participation.destroy');
//            Route::get('/conversations/{id}/participants/{participation_id}', 'ConversationParticipationController@show')
//                ->name('conversations.participation.show');
//            Route::put('/conversations/{id}/participants/{participation_id}', 'ConversationParticipationController@update')
//                ->name('conversations.participation.update');
//            Route::get('/conversations/{id}/participants', 'ConversationParticipationController@index')
//                ->name('conversations.participation.index');
//
//            /* Messaging */
//            Route::post('/conversations/{id}/messages', 'ConversationMessageController@store')
//                ->name('conversations.messages.store');
//            Route::get('/conversations/{id}/messages', 'ConversationMessageController@index')
//                ->name('conversations.messages.index');
//            Route::get('/conversations/{id}/messages-cursor', 'ConversationMessageController@indexWithCursor')
//                ->name('conversations.messages.index.cursor');
//            Route::get('/conversations/{id}/messages/{message_id}', 'ConversationMessageController@show')
//                ->name('conversations.messages.show');
//            Route::delete('/conversations/{id}/messages', 'ConversationMessageController@deleteAll')
//                ->name('conversations.messages.destroy.all');
//            Route::delete('/conversations/{id}/messages/{message_id}', 'ConversationMessageController@destroy')
//                ->name('conversations.messages.destroy');
//        });


    });
});

Route::fallback(function () {
    return response()->json([
        'Hey_there!' => 'Ramadan Mubarak!!',
        'message' => 'Resource not found, the API endpoint does not exist',
        'documentation' => 'https://0yviq6a5i5.apidog.io/',
        'version' => 'API v1 - Devhub is a platform for developers to share knowledge, collaborate on projects, and connect with other developers.',
    ], 404);
});
// for testing
Route::post('broadcast-test', [MessageController::class, 'broadcastTest']);
