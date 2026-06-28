<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'message' => 'Devhub Community API v2.0.0',
            'status' => 'OK - Server (Debian) is running',
            'base_url' => 'https://dev-hubs.tech/api/v1',
            'api_docs' => 'https://devhub.apidog.io/',
            'admin_panel' => 'https://dev-hubs.tech/admin',
            'mentoring' => 'https://dev-hubs.tech/pulse'
        ]);
    });
});

Route::fallback(function () {
    return response()->json([
        'message' => 'Resource not found, the API endpoint does not exist , can visit the documentation for more details',
        'documentation' => 'https://devhub.apidog.io'
    ], 404);
});

require __DIR__ . '/auth.php';
require __DIR__ . '/ai.php';
require __DIR__ . '/chat.php';
require __DIR__ . '/notification.php';
require __DIR__ . '/profile.php';
require __DIR__ . '/test.php';
require __DIR__ . '/setting.php';
require __DIR__ . '/tag.php';
require __DIR__ . '/post.php';
require __DIR__ . '/list.php';
require __DIR__ . '/editor.php';
require __DIR__ . '/question.php';
require __DIR__ . '/topics.php';
require __DIR__ . '/follow.php';
