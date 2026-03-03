<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Auth\SocialiteMediaController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/chats', function () {
    return view('Chat-users.test-pusher-chat');
});

Route::get('/send-notification', function () {
    return view('send-notification');
})->name('send.notification');

