<?php

use App\Http\Controllers\V1\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {
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
    });
});
