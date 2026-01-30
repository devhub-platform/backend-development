<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Auth\SocialiteMediaController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/me', function () {
    return view('welcome');
});