<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Auth\SocialiteMediaFrontController;

Route::redirect('/', 'https://devhub-platform.github.io/frontend-development/');

Route::get('/chats', function () {
    return view('Chat-users.test-pusher-chat');
});


