<?php

use App\Http\Controllers\V1\FollowersController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

        Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {

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

        });
});


