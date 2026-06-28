<?php

use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\ForgetPasswordController;
use App\Http\Controllers\V1\Auth\SocialiteMediaFlutterController;
use App\Http\Controllers\V1\Auth\SocialiteMediaFrontController;
use App\Http\Controllers\V1\Auth\VerifyEmailController;
use App\Http\Controllers\V1\VerifyAltEmailController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:10,1')->group(function () {

    Route::controller(SocialiteMediaFrontController::class)->group(function () {
        Route::get('auth/', 'loginGoogle');
        Route::get('auth/callback', 'callbackGoogle');

        Route::get('auth/github/', action: 'loginGithub');
        Route::get('/front/auth/github/callback', action: 'callbackGithub');
    });

    Route::controller(SocialiteMediaFlutterController::class)->group(function () {
        Route::post('mobile/auth/google/login', 'loginGoogle');
        Route::get('auth/google/callback', 'callbackGoogle');

        Route::post('mobile/auth/github/login', 'loginGithub');
        Route::get('auth/github/callback', 'callbackGithub');
    });

    Route::controller(AuthController::class)->group(function () {
        Route::post('login', 'login');
        Route::post('register', 'register');
    });

    Route::controller(VerifyEmailController::class)->middleware('throttle:5,1')->group(function () {
        Route::post('email/verify-otp', 'verifyEmailOtp');
        Route::post('email/send-otp', 'sendEmailOTP');
        Route::get('email/is-verified', 'isVerified');
    });

    Route::controller(ForgetPasswordController::class)->middleware('throttle:5,1')->group(function () {
        Route::post('password/forgot', 'forgetPassword');
        Route::post('password/verify-otp', 'verifyOtp');
        Route::post('password/reset', 'resetPassword');
    });

    Route::middleware(['auth:api', 'throttle:30,1', 'verified'])->group(function () {
        Route::controller(AuthController::class)->group(function () {
            Route::post('logout', 'logout');
            Route::post('refresh', 'refreshToken');
            Route::get('me', 'user');
        });
    });
});
