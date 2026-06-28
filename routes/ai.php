<?php

use A\AnswerController;
use App\Http\Controllers\V1\AiModels\AIChatController;
use App\Http\Controllers\V1\AiModels\AITopicsUserController;
use App\Http\Controllers\V1\AiModels\AttachmentController;
use App\Http\Controllers\V1\AiModels\HistoryController;
use App\Http\Controllers\V1\AiModels\PostChatController;
use App\Http\Controllers\V1\AiModels\QuestionChatController;
use App\Http\Controllers\V1\Chats\ChatController;
use App\Http\Controllers\V1\PostController;
use App\Http\Controllers\V1\Posts\PostAIController;
use App\Http\Controllers\V1\QuestionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\TrendingController;

Route::prefix('v1')->group(function () {

    Route::get('ai-topics-user/{userId}', [AITopicsUserController::class, 'showByUserId']);
    Route::get('recommendations', [RecommendationController::class, 'index']);

    Route::get('ai-chat/models', [AIChatController::class, 'models']);
    Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {

        // ─── Post AI ──────────────────────────────────────────────────────────
        Route::prefix('posts/ai')->middleware('throttle:10,1')->controller(PostAIController::class)
            ->group(function () {
                Route::post('generate-image', 'generateImage');
                Route::post('generate-content', 'generateContent');
                Route::delete('generated-images/{id}', 'discardImage');
            });

        Route::post('posts/{post}/ai-chat', [PostChatController::class, 'chat']);

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
                Route::get('/tech', 'tech');
                Route::get('/tech/{id}', 'techDetail');
            });
    });
});
