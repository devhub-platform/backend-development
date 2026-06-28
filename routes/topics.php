<?php


use App\Http\Controllers\V1\TopicController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {

        // ─── Topics (User Selection) ───────────────────────────────────────────
        Route::controller(TopicController::class)->prefix('topics')->group(function () {
            Route::get('my-topics', 'getUserTopics');
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::delete('{topic}', 'destroy');
            Route::put('{topic}', 'update');
            Route::post('add', 'addTopics');
            Route::post('remove', 'removeTopics');
            Route::post('clear', 'clearTopics');
            Route::post('onboarding/complete', 'completeOnboarding');
            Route::get('onboarding/status', 'getOnboardingStatus');
        });
    });
});


