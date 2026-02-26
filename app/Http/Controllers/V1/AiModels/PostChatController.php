<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\V1\Controller;
use App\Models\Post;
use App\Services\Chat\ChatRateLimiter;
use App\Services\Chat\PostChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostChatController extends Controller
{
    public function __construct(
        protected PostChatService  $service,
        protected ChatRateLimiter  $limiter,
    ) {}

    public function chat(Request $request, Post $post): JsonResponse
    {
        $this->limiter->check($request->user()->id);

        $validated = $request->validate([
            'message'    => 'required|string|max:2000',
            'session_id' => 'nullable|integer|exists:ai_chat_sessions,id',
        ]);

        $result = $this->service->handle(
            post:      $post,
            message:   $validated['message'],
            sessionId: $validated['session_id'] ?? null,
            userId:    $request->user()->id,
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
