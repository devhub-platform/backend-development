<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\V1\Controller;
use App\Services\Chat\ChatRateLimiter;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIChatController extends Controller
{
    public function __construct(
        protected ChatService     $chat,
        protected ChatRateLimiter $limiter
    ) {}

    public function chat(Request $request): JsonResponse
    {
        // Check rate limit before processing the request
        $this->limiter->check($request->user()?->id);

        $validModelIds = array_column(config('ai_models.chat', []), 'id');

        $validated = $request->validate([
            'session_id'    => 'nullable|exists:ai_chat_sessions,id',
            'model'         => ['required', 'string', 'in:' . implode(',', $validModelIds)],
            'message'       => 'required|string|max:1500',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'integer|exists:attachments,id',
        ]);

        $response = $this->chat->handle([
            'session_id'  => $validated['session_id'] ?? null,
            'model'       => $validated['model'],
            'message'     => $validated['message'],
            'attachments' => $validated['attachments'] ?? [],
        ], $request->user());

        return response()->json([
            'session_id'         => $response['session_id'] ?? null,
            'ai_message'         => $response['content'] ?? 'No response',
            'model_used'         => $validated['model'],
            'model_resolved'     => $response['model_used'] ?? $validated['model'],
            'processing_time_ms' => $response['processing_time_ms'] ?? 0,
            'success'            => $response['success'] ?? false,
        ]);
    }

    public function models(): JsonResponse
    {
        return response()->json([
            'models' => config('ai_models.chat'),
        ]);
    }
}
