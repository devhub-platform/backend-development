<?php

use App\Http\Controllers\V1\ReadingListController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {

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
    });
});
