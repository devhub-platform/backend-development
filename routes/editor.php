<?php


use App\Http\Controllers\V1\CodeEditorController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {

        // ─── Code Editor ──────────────────────────────────────────────────────
        Route::controller(CodeEditorController::class)->group(function () {
            Route::get('code/runtimes', 'runtimes');
            Route::post('code/execute', 'execute');
            Route::get('code/search-runtimes', 'searchInRuntimes');
            Route::get('code/languages', 'languages');
        });
    });
});


