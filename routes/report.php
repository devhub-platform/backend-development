<?php

use App\Http\Controllers\V1\FeedbackController;
use App\Http\Controllers\V1\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {

        Route::controller(ReportController::class)->group(function () {
            Route::post('reports/block/{target}', 'block');
            Route::post('reports/report/{target}', 'report');
            Route::post('reports/unblock/{target}', 'unblock');
            Route::get('reports/reported-users', 'reportedUsers');
            Route::get('reports/blocked-users', 'blockList');
            Route::get('reports/reported-users', 'reportList');
            Route::get('reports/reasons', 'reason');
        });

        Route::controller(FeedbackController::class)->group(function () {
            Route::post('feedbacks', 'store');
            Route::get('feedbacks/admin/all', 'getAllFeedback');
            Route::patch('feedbacks/{feedbackId}/status', 'updateStatus');
            Route::get('feedbacks/admin/statistics', 'statistics');
        });
    });
});

