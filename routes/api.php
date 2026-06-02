<?php

use App\Http\Controllers\V1\AiModels\AIChatController;
use App\Http\Controllers\V1\Chats\ChatController;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\V1\AiModels\AttachmentController;
use App\Http\Controllers\V1\AiModels\HistoryController;
use App\Http\Controllers\V1\AiModels\PostChatController;
use App\Http\Controllers\V1\AiModels\QuestionChatController;
use App\Http\Controllers\V1\Posts\PostAIController;
use App\Http\Controllers\V1\QuestionController;
use App\Http\Controllers\V1\AnswerController;
use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\ForgetPasswordController;
use App\Http\Controllers\V1\Auth\SocialiteMediaFrontController;
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
use App\Http\Controllers\V1\TopicController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\V1\UserStatusesController;
use App\Http\Controllers\V1\VerifyAltEmailController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Chats\MessageController;
use App\Http\Controllers\V1\Auth\SocialiteMediaFlutterController;
use App\Http\Controllers\V1\TestNotificationController;
use App\Http\Controllers\V1\Chats\UserOnlineController;
use App\Http\Controllers\V1\Chats\UserOfflineController;
use App\Http\Controllers\V1\Chats\UserPresenceShowController;
use App\Http\Controllers\V1\FeedbackController;
use App\Http\Controllers\V1\TrendingController;
use App\Http\Controllers\V1\AiModels\AITopicsUserController;
use App\Http\Controllers\V1\RecommendationController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\V1\ProfileQuestionController;

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'message' => 'Devhub Community API v2.0.0',
            'status' => 'OK - Server on nest-server is running',
            'base_url' => 'https://dev-hubs.tech/api/v1',
            'api_docs' => 'https://devhub.apidog.io/',
            'admin_panel' => 'https://dev-hubs.tech/admin',
            'mentoring' => 'https://dev-hubs.tech/pulse'
        ]);
    });

    Route::middleware('throttle:30,1')->group(function () {
        Route::controller(SocialiteMediaFrontController::class)->group(function () {
            Route::get('auth/', 'loginGoogle');
            Route::get('auth/callback', 'callbackGoogle');

            Route::get('auth/github/', action: 'loginGithub');
            Route::get('/front/auth/github/callback', action: 'callbackGithub');
        });

        Route::controller(SocialiteMediaFlutterController::class)->group(function () {
            Route::post('mobile/auth/google/login', 'loginGoogle');
            Route::get('auth/google/callback', 'callbackGoogle');

            Route::post('mobile/auth/github/login', 'loginGithub');
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

        Route::get('ai-topics-user/{userId}', [AITopicsUserController::class, 'showByUserId']);
        // Public recommendations endpoint used by the front-page to fetch suggested topics/posts
        Route::get('recommendations', [RecommendationController::class, 'index']);
        //        Route::get('ai-topics-user', [AITopicsUserController::class, 'index']);

        // ─── PUBLIC ROUTES - Content Viewing (No Auth Required) ───────────────────────────────
        Route::controller(PostController::class)->group(function () {
            Route::get('posts', 'index');                    // Home feed
            Route::get('posts/recent', 'recentPosts');      // Recent posts
            Route::get('posts/top-views', 'topPostsViews'); // Top posts
            Route::get('posts/{post}', 'show');             // Single post view
            Route::get('posts/{post}/tags', 'postsTags');
            Route::get('posts/{post}/tags-list', 'postsTagsList');
        });

        Route::controller(PostViewController::class)->group(function () {
            Route::get('posts/viewed/recent', 'getRecentViewedPosts');
        });

        Route::controller(QuestionController::class)->group(function () {
            Route::get('questions', 'index');
            Route::get('questions/hot', 'trending');
            Route::get('questions/{question}', 'show');
        });

        Route::controller(AnswerController::class)->group(function () {
            Route::get('questions/{question}/answers', 'index');
            Route::get('questions/{question}/answers/{answer}', 'show');
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
            Route::get('users/{user}/mutual-following', 'getMutualFollowing');
            Route::get('users/{user}/mutual-check', 'checkMutualFollowing');
        });

        Route::controller(SearchController::class)->group(function () {
            Route::get('search/posts', 'searchPosts');
            Route::get('search/users', 'searchUsersByUsername');
            Route::get('search/tags', 'searchTags');
        });

        Route::controller(CommentController::class)->group(function () {
            Route::get('posts/{postId}/comments', 'getByPost');
            Route::get('posts/{postId}/comments/count', 'countByPost');
            Route::get('users/{userId}/comments', 'getByUser');
            Route::get('comments/{comment}/replies', 'getReplies');
            Route::get('comments/{comment}/thread', 'getThread');
            Route::get('comments/{comment}/reactions', 'getReactions');
        });

        Route::controller(TagController::class)->group(function () {
            Route::get('tags/popular', 'popularTag');
            Route::get('tags', 'allTags');
        });

        Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {
            Route::controller(AuthController::class)->group(function () {
                Route::post('logout', 'logout');
                Route::post('refresh', 'refreshToken');
                Route::get('me', 'user');
            });

            // ─── Topics (User Selection) ───────────────────────────────────────────
            Route::controller(TopicController::class)->prefix('topics')->group(function () {
                Route::get('my-topics', 'getUserTopics');
                Route::get('/', 'index');

                Route::post('/', 'store');           // Admin only
                Route::delete('{topic}', 'destroy'); // Admin only
                Route::delete('{topic}', 'update');  // Admin only

                Route::post('add', 'addTopics');
                Route::post('remove', 'removeTopics');
                Route::post('clear', 'clearTopics');
            });

            // ─── Posts - Write Actions ────────────────────────────────────────────────────────
            Route::controller(PostController::class)->group(function () {
                Route::post('posts', 'store');                   // Create post
                Route::put('posts/{post}', 'update');           // Update post
                Route::delete('posts/{post}', 'destroy');       // Delete post (soft delete)
                Route::post('posts/{post}/restore', 'restore'); // Restore post
                Route::delete('posts/{post}/force', 'forceDelete'); // Permanent delete

                Route::get('user/posts', 'userPosts');          // My posts
                Route::get('posts/drafts', 'drafts');           // My drafts
                Route::get('posts/archives', 'archivesTrashed'); // My archived
                Route::get('posts/report/reasons', 'reasonsToReport');
                Route::post('posts/{post}/report', 'reportPost'); // Report a post
                Route::get('posts/{post}/comments', 'postComments');
            });

            // ─── Post AI ──────────────────────────────────────────────────────────
            Route::prefix('posts/ai')->middleware('throttle:10,1')->controller(PostAIController::class)
                ->group(function () {
                    Route::post('generate-image', 'generateImage');
                    Route::post('generate-content', 'generateContent');
                    Route::delete('generated-images/{id}', 'discardImage');
                });

            Route::post('posts/{post}/ai-chat', [PostChatController::class, 'chat']);

            Route::controller(PostViewController::class)->group(function () {
                Route::delete('posts/viewed/clear', 'clearViewedPosts');
            });

            Route::controller(QuestionController::class)->group(function () {
                Route::post('questions/create', 'store');
                Route::put('questions/{question}', 'update');
                Route::delete('questions/{question}', 'destroy');
                Route::post('questions/{question}/vote', 'vote');
            });

            Route::controller(AnswerController::class)->group(function () {
                Route::post('questions/{question}/answers/create', 'store');
                Route::put('questions/{question}/answers/{answer}', 'update');
                Route::delete('questions/{question}/answers/{answer}', 'destroy');
                Route::post('questions/{question}/answers/{answer}/vote', 'vote');
                Route::post('questions/{question}/answers/{answer}/accept', 'accept');
                Route::post('questions/{question}/answers/{answer}/unaccept', 'unaccept');
            });

            Route::post('questions/{question}/ai-chat', [QuestionChatController::class, 'chat']);

            // ─── Comments - Write Actions ─────────────────────────────────────────────────────
            Route::middleware('blocked.user')->controller(CommentController::class)->group(function () {
                Route::post('posts/{post}/comments', 'store');
                Route::post('posts/{post}/comments/{parentComment}/reply', 'reply');
                Route::post('comments/{comment}/react', 'react');
                Route::delete('comments/{comment}/remove-react', 'removeReaction');
            });

            Route::controller(CommentController::class)->group(function () {
                Route::post('comments/{comment}/pin', 'pin');
                Route::post('comments/{comment}/unpin', 'unpin');
                Route::get('comments/{comment}/my-reaction', 'myReaction');
                Route::delete('comments/{id}/force', 'forceDelete');
                Route::get('my/comments', 'myRecentComments');
                Route::get('my/comments/stats', 'myCommentStats');
                Route::delete('search/clear', 'clearSearchHistory');
            });

            // ─── Tags - Write Actions ────────────────────────────────────────────────────────
            Route::controller(TagController::class)->group(function () {
                Route::post('tags', 'store');
                Route::post('posts/{post}/tags', 'attachTagsToPost');
                Route::delete('posts/{post}/tags/{tag}', 'detachTagFromPost');
            });

            // ─── Profile ──────────────────────────────────────────────────────────
            Route::controller(ProfileController::class)->group(function () {
                Route::get('profile', 'show');
                Route::patch('profile', 'update');
                Route::get('profile/user/posts', 'userPosts');
                Route::get('profile/user/comments', 'userComments');
                Route::get('profile/user/tags', 'userTags');
                Route::post('profile/upload/avatar', 'uploadAvatarImage');
                Route::post('profile/upload/cover-image', 'uploadCoverImage');

                Route::post('profile/upload/cv', 'uploadCv');
                Route::delete('profile/delete/cv', 'deleteCv');

                Route::get('profile/activity', 'activity');
                Route::get('profile/details', 'details');
                Route::get('profile/share-link', 'shareLink');
            });
            Route::prefix('profile')->middleware('auth:api')->group(function () {
                // My own questions
                Route::get('questions', [ProfileQuestionController::class, 'myQuestions']);

                // Any user's questions
                Route::get('{userId}/questions', [ProfileQuestionController::class, 'userQuestions']);
            });

            // ─── Followers ────────────────────────────────────────────────────────
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

            // ─── Saved Posts ──────────────────────────────────────────────────────
            Route::controller(SavedPostController::class)->group(function () {
                Route::get('saved-posts', 'index');
                Route::post('saved-posts/{post}', 'store');
                Route::delete('saved-posts/{post}', 'destroy');
            });

            // ─── Notifications ────────────────────────────────────────────────────
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
                Route::patch('notifications/preferences', 'updateNotificationPreferences');
                Route::patch('notifications/preferences/{type}/toggle', 'toggleNotificationPreference');

                Route::patch('notifications/add-player-id', 'storePlayerId');
                Route::delete('notifications/remove-player-id', 'removePlayerId');

                Route::get('notifications/questions', 'getQuestionsNotifications');
                Route::get('notifications/answers', 'getAnswersNotifications');
            });

            // ─── User Interaction Reporting & Analytics ──────────────────────────
            Route::controller(\App\Http\Controllers\V1\UserInteractionReportController::class)->group(function () {
                Route::get('user/interaction-report', 'getInteractionHistory');
                Route::get('user/interaction-breakdown/{topicId}', 'getInteractionBreakdown');
                Route::get('user/recommended-topics', 'getRecommendedTopics');
                Route::get('user/topic-report/{topicId}', 'getTopicReport');
                Route::get('user/interaction-analytics', 'getUserAnalytics');
            });

            // ─── Tags Follow ───────────────────HH───────────────────────────────────
            Route::controller(TagFollowController::class)->group(function () {
                Route::post('tags/{tag}/follow', 'follow');
                Route::delete('tags/{tag}/unfollow', 'unfollow');
                Route::get('tags/{tag}/followers', 'listFollowing');
            });

            // ─── User Statuses ────────────────────────────────────────────────────
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

            // ─── Reading Lists ────────────────────────────────────────────────────
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

            // ─── Code Editor ──────────────────────────────────────────────────
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

                Route::get('reports/reported-users', 'reportedUsers');
                Route::get('reports/blocked-users', 'blockList');

                Route::get('reports/reported-users', 'reportList');
                Route::get('reports/reasons', 'reason');
            });

            // ─── Settings ─────────────────────────────────────────────────────────
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
                Route::post('settings/alt-email/send-reset-otp', 'resendAltEmailOtp');
                Route::post('settings/alt-email/make-as-primary', 'makeAsPrimaryEmail');
            });

            Route::controller(FeedbackController::class)->group(function () {
                Route::post('feedbacks', 'store');
                Route::get('feedbacks/admin/all', 'getAllFeedback');
                Route::patch('feedbacks/{feedbackId}/status', 'updateStatus');
                Route::get('feedbacks/admin/statistics', 'statistics');
            });

            // ─── Chat ─────────────────────────────────────────────────────────────
            Route::prefix('chat')->controller(ChatController::class)->group(function () {
                Route::get('conversations', 'index');
                Route::post('conversations', 'createOrGetConversation');
                Route::get('conversations/{conversation}', 'show');
                Route::delete('conversations/clear/{conversation}', 'destroy');
                Route::delete('conversations/{conversationId}', 'deleteConversation');
                Route::get('conversations/{conversation}/messages', 'getMessages');
                Route::post('conversations/{conversation}/messages', 'sendMessage');
                Route::delete('conversations/{conversation}/messages/{messageId}', 'deleteMessage');
                Route::post('conversations/{conversation}/messages/read', 'markAsRead');
                Route::get('unread-count', 'unreadCount');
            });

            Route::prefix('messages')->controller(MessageController::class)->group(function () {
                Route::post('/conversation/{conversationId}/send', 'sendMessage');
                Route::post('/conversation/{conversationId}/send-attachment', 'sendMessageWithAttachment');
                Route::post('/conversation/{conversationId}/send-voice', 'sendVoiceMessage');
                Route::delete('{messageId}/conversation/{conversationId}', 'deleteMessage');
                Route::post('/conversation/{conversationId}/mark-as-read', 'markAsRead');
                Route::put('{messageId}/conversation/{conversationId}', 'updateMessage');
                Route::post('{messageId}/conversation/{conversationId}/reaction', 'addReactionToMessage');
                Route::post('{messageId}/conversation/{conversationId}/flag', 'makeMessageAsFlagged');
            });

            Route::prefix('chat/presence')->group(function () {
                Route::put('online', UserOnlineController::class);
                Route::put('offline', UserOfflineController::class);
                Route::get('users/{user}', UserPresenceShowController::class);
            });

            // ─── AI Chat ──────────────────────────────────────────────────────────
            Route::prefix('ai-chat')->group(function () {
                Route::post('send', [AIChatController::class, 'chat']);
                Route::get('/prompts/usage', [AIChatController::class, 'promptUsage']);

                Route::controller(AttachmentController::class)->prefix('attachments')->middleware('throttle:10,1')->group(function () {
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

            // ─── Trending ────────────────────────────────────────────────────────
            Route::prefix('trending')->middleware('throttle:30,1')->controller(TrendingController::class)
                ->group(function () {
                    Route::get('posts', 'posts');
                    Route::get('tags', 'tags');
                    // Tech Trends Feed — lightweight, instant
                    Route::get('/tech', 'tech');
                    // Tech Trend Detail — with AI enrichment on-demand
                    Route::get('/tech/{id}', 'techDetail');
                });
        });
    });
});

Route::fallback(function () {
    return response()->json([
        'message' => 'Resource not found, the API endpoint does not exist , can visit the documentation for more details',
    ], 404);
});

Route::post('/test/send-message', [MessageController::class, 'broadcastTest']);
Route::post('/send-message_notification', TestNotificationController::class);

Route::get('/test-redis', function () {
    Redis::set('test_key', 'I Love Devhub!');
    Log::info('Set test_key in Redis: Hello Redis Cloud!');
    $key = Redis::get('test_key');
    return response()->json([
        'message' => 'Redis connection successful!',
        'test_key_value' => $key,
    ]);
});

Route::post('upload-on-azure', function (\App\Services\AzureBlobStorageService $azureService) {
    $file = request()->file('file');

    if (!$file) {
        return response()->json(['error' => 'No file provided'], 400);
    }

    $filePath = $azureService->uploadImage($file, 'devhub');

    if (!$filePath) {
        return response()->json(['error' => 'Failed to upload file to Azure Blobs'], 500);
    }

    return response()->json([
        'file_path' => $filePath,
        'message' => 'File uploaded successfully to Azure Blobs',
    ]);
});
