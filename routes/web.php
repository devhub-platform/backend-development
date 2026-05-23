<?php

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use STS\Phpinfo\Info;

Route::get('/', function () {
//    return view('welcome');
//    $info = Info::capture();
//    $info->version();              // "8.4.19"
//    $info->hasModule('mysqli');     // true
//    $info->config('post_max_size');           // "32M" (local/effective value)
//    $info->config('post_max_size', 'master'); // "2M" (php.ini default)
//    $info->os();       // "Linux"
//    $info->hostname(); // "my-server"
    prettyphpinfo();

});
//Route::get('/', function () {
//
//    $info = Info::capture();
//
//    return response()->json([
//        'php_version' => $info->version(),
//        'os' => $info->os(),
//        'hostname' => $info->hostname(),
//        'post_max_size' => $info->config('post_max_size'),
//        'mysqli' => $info->hasModule('mysqli'),
//        ''
//    ]);
//
//});

Route::get('/chats', function () {
    return view('Chat-users.test-pusher-chat');
});

$searchPage = function (Request $request) {
    $posts = Post::search($request->input('query'))->paginate(10);
    return view('algolia', compact('posts'));
};

Route::get('search', $searchPage);
Route::get('algolia', $searchPage);
