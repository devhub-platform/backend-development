<?php

use App\Http\Controllers\V1\ProfileController;
use App\Http\Controllers\V1\ProfileQuestionController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\V1\UserStatusesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\UserInteractionReportController;

Route::prefix('v1')->group(function () {

    Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {

        Route::controller(UserController::class)->group(function () {
            Route::get('users', 'index');
            Route::get('users/recommended', 'getRecommendedUsers');
            Route::get('users/{id}', 'showUserProfile');
            Route::get('users/{id}/similar-skills', 'getUsersWithSimilarSkills');
            Route::get('users/{user}/posts', 'userPosts');
            Route::get('users/{user}/comments', 'userComments');
            Route::get('users/{user}/tags', 'userTags');
            Route::get('users/{user}/followers', 'usersFollowers');
            Route::get('users/{user}/followers/count', 'usersFollowersCount');
            Route::get('users/{user}/following', 'usersFollowing');
            Route::get('users/{user}/mutual-followers', 'getMutualFollowers');
            Route::get('users/{user}/mutual-following', 'getMutualFollowing');
            Route::get('users/{user}/mutual-check', 'checkMutualFollowing');
        });

        // ─── Profile ──────────────────────────────────────────────────────────
        Route::controller(ProfileController::class)->group(function () {
            Route::get('profile', 'show');
            Route::patch('profile', 'update');
            Route::get('profile/user/posts', 'userPosts');
            Route::get('profile/user/comments', 'userComments');
            Route::get('profile/user/tags', 'userTags');
            Route::post('profile/upload/avatar', 'uploadAvatarImage');
            Route::post('profile/upload/cover-image', 'uploadCoverImage');
            Route::post('profile/upload/cv', 'uploadCv');
            Route::delete('profile/delete/cv', 'deleteCv');
            Route::get('profile/activity', 'activity');
            Route::get('profile/details', 'details');
            Route::get('profile/share-link', 'shareLink');
        });

        Route::prefix('profile')->middleware('auth:api')->group(function () {
            Route::get('questions', [ProfileQuestionController::class, 'myQuestions']);
            Route::get('{userId}/questions', [ProfileQuestionController::class, 'userQuestions']);
        });

        // ─── User Interaction Reporting & Analytics ───────────────────────────
        Route::controller(UserInteractionReportController::class)->group(function () {
            Route::get('user/interaction-report', 'getInteractionHistory');
            Route::get('user/interaction-breakdown/{topicId}', 'getInteractionBreakdown');
            Route::get('user/recommended-topics', 'getRecommendedTopics');
            Route::get('user/topic-report/{topicId}', 'getTopicReport');
            Route::get('user/interaction-analytics', 'getUserAnalytics');
        });

        // ─── User Statuses ────────────────────────────────────────────────────
        Route::controller(UserStatusesController::class)->group(function () {
            Route::get('user/statuses', 'getStatuses');
            Route::post('user/statuses', 'store');
            Route::patch('user/statuses', 'update');
            Route::delete('user/statuses', 'delete');
            Route::post('user/statuses/set-busy', 'setBusy');
            Route::post('user/statuses/set-available', 'setAvailable');
            Route::post('user/statuses/clear-expired', 'clearExpiredStatuses');
            Route::get('users/{username}/status', 'getUserStatus');
        });
    });
});
