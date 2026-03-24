<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Auth\SocialiteMediaFrontController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/chats', function () {
    return view('Chat-users.test-pusher-chat');
});
//
//Route::get('/test/notification', function () {
//    return view('test-notification-api');
//});

//Route::get('/realtime-notifications', function () {
//    return view('realtime-notifications');
//});