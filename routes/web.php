<?php

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use STS\Phpinfo\Info;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/info', function () {
    prettyphpinfo();
});

Route::get('/chats', function () {
    return view('Chat-users.test-pusher-chat');
});

//$searchPage = function (Request $request) {
//    $posts = Post::search($request->input('query'))->paginate(10);
//    return view('algolia', compact('posts'));
//};
//
//Route::get('search', $searchPage);
//Route::get('algolia', $searchPage);
