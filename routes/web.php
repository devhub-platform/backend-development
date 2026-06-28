<?php

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use STS\Phpinfo\Info;

//Route::get('/', function () {
//    return view('welcome');
//});

Route::get('/', function () {
    return response()->json([
        'message' => 'Devhub Community API v3.0.0',
        'info' => 'https://dev-hubs.tech/info',
        'status' => 'OK - Server(debian 2gb ram , 2 core cpu , 16gb ssd) is running',
        'base_url' => 'https://dev-hubs.tech/api/v1',
        'api_docs' => 'https://devhub.apidog.io/',
        'admin' => 'https://dev-hubs.tech/admin',
    ]);
});

Route::get('/info', function () {
    prettyphpinfo();
});

Route::get('/chats', function () {
    return view('Chat-users.test-pusher-chat');
});
