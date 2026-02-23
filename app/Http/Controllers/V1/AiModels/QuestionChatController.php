<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Services\Chat\QuestionChatService;
use App\Services\Chat\ChatRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuestionChatController extends Controller
{
    public function __construct(
        protected QuestionChatService $service,
        protected ChatRateLimiter     $limiter,
    ) {}

    public function chat(Request $request, Question $question): JsonResponse
    {
        $this->limiter->check($request->user()->id);

        $validated = $request->validate([
            'message'    => 'required|string|max:2000',
            'session_id' => 'nullable|integer|exists:ai_chat_sessions,id',
        ]);

        $result = $this->service->handle(
            question:  $question,
            message:   $validated['message'],
            sessionId: $validated['session_id'] ?? null,
            userId:    $request->user()->id,
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
