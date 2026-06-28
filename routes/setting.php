<?php

use App\Http\Controllers\V1\SettingController;
use App\Http\Controllers\V1\VerifyAltEmailController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware('throttle:30,1')->group(function () {
        Route::middleware(['auth:api', 'throttle:25,1', 'verified'])->group(function () {

            Route::controller(SettingController::class)->group(function () {
                Route::patch('settings/update-password', 'updatePassword');
                Route::post('settings/social-accounts', 'addSocialAccounts');
                Route::delete('settings/soft/delete-account', 'delete');
                Route::delete('settings/force/delete-account', 'forceDelete');
            });

            Route::controller(VerifyAltEmailController::class)->group(function () {
                Route::post('settings/alt-email/send-otp', 'addAltEmail');
                Route::post('settings/alt-email/verify-otp', 'verifyAltEmail');
                Route::delete('settings/alt-email/remove', 'removeAltEmail');
                Route::post('settings/alt-email/send-reset-otp', 'resendAltEmailOtp');
                Route::post('settings/alt-email/make-as-primary', 'makeAsPrimaryEmail');
            });
        });
    });
});

