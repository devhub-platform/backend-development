<?php

use App\Http\Controllers\V1\AnswerController;
use App\Http\Controllers\V1\QuestionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {


    Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {

        // ─── Questions ────────────────────────────────────────────────────────
        Route::controller(QuestionController::class)->group(function () {
            Route::get('questions', 'index');
            Route::post('questions/create', 'store');
            Route::get('questions/hot', 'trending');
            Route::get('questions/search', 'search');
            Route::get('questions/by-tag/{tag}', 'byTag');
            Route::get('questions/{question}/share', 'share');
            Route::get('questions/{question}', 'show');
            Route::put('questions/{question}', 'update');
            Route::delete('questions/{question}', 'destroy');
            Route::post('questions/{question}/vote', 'vote');
            Route::post('questions/{question}/ai-chat', 'chat');
        });

        // ─── Answers ──────────────────────────────────────────────────────────
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
    });
});

