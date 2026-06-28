<?php

use App\Http\Controllers\V1\Chats\ChatController;
use App\Http\Controllers\V1\Chats\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {
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

    });
});
