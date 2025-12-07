<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Auth\SocialiteMediaController;

Route::get('/', function () {
    return view('welcome');
});


Route::prefix('v1')->controller(SocialiteMediaController::class)->group(function () {
    Route::get('auth/github/login', 'loginGithub');
    Route::get('auth/github/callback', 'callbackGithub');
});
