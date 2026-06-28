<?php

use App\Http\Controllers\V1\Chats\MessageController;
use App\Http\Controllers\V1\TestNotificationController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use App\Services\AzureBlobStorageService;

Route::post('/test/send-message', [MessageController::class, 'broadcastTest']);
Route::post('/send-message_notification', TestNotificationController::class);
Route::get('/test-redis', function () {
    Redis::set('test_key', 'I Love Devhub!');
    Log::info('Set test_key in Redis: Hello Redis Cloud!');
    $key = Redis::get('test_key');
    return response()->json([
        'message' => 'Redis connection successful!',
        'test_key_value' => $key,
    ]);
});
Route::post('upload-on-azure', function (AzureBlobStorageService $azureService) {
    $file = request()->file('file');

    if (!$file) {
        return response()->json(['error' => 'No file provided'], 400);
    }

    $filePath = $azureService->uploadImage($file, 'devhub');

    if (!$filePath) {
        return response()->json(['error' => 'Failed to upload file to Azure Blobs'], 500);
    }

    return response()->json([
        'file_path' => $filePath,
        'message' => 'File uploaded successfully to Azure Blobs',
    ]);
});
