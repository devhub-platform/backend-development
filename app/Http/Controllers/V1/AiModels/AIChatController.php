<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Chat\ChatService;

class AIChatController extends Controller
{
    public function __construct(
        protected ChatService $chat
    )
    {
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'nullable|exists:ai_chat_sessions,id',
            'model' => 'required|string',
            'message' => 'required|string|max:1500',
            'attachments' => 'nullable|array',
            'attachments.*' => 'integer|exists:attachments,id'
        ]);

        $data = [
            'session_id' => $validated['session_id'] ?? null,
            'model' => $validated['model'],
            'message' => $validated['message'],
            'attachments' => $validated['attachments'] ?? []
        ];

        $response = $this->chat->handle($data, $request->user());

        return response()->json([
            'session_id' => $response['session_id'] ?? null,
            'ai_message' => $response['content'] ?? 'No response',
            'model_used' => $response['model_used'] ?? $validated['model'],
            'processing_time_ms' => $response['processing_time_ms'] ?? 0,
            'success' => $response['success'] ?? false
        ]);
    }

    public function models(): JsonResponse
    {
        return response()->json([
            'models' => config('ai_models.chat'),
        ]);
    }
}
