<?php

use App\Http\Controllers\V1\TagController;
use App\Http\Controllers\V1\TagFollowController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {

        // ─── Tags ─────────────────────────────────────────────────────────────
        Route::controller(TagController::class)->group(function () {
            Route::get('tags/popular', 'popularTag');
            Route::get('tags', 'allTags');
            Route::post('tags', 'store');
            Route::post('posts/{post}/tags', 'attachTagsToPost');
            Route::delete('posts/{post}/tags/{tag}', 'detachTagFromPost');
        });


        // ─── Tags Follow ──────────────────────────────────────────────────────
        Route::controller(TagFollowController::class)->group(function () {
            Route::post('tags/{tag}/follow', 'follow');
            Route::delete('tags/{tag}/unfollow', 'unfollow');
            Route::get('tags/{tag}/followers', 'listFollowing');
        });

    });
});

