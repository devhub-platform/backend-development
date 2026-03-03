<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use App\Models\AIChatSession;
use App\Services\Chat\ChatRateLimiter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseChatController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ChatRateLimiter $limiter,
    ) {}

    protected function validateSession(Request $request, ?int $sessionId): ?JsonResponse
    {
        if (empty($sessionId)) return null;

        $session = AIChatSession::where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        return null;
    }

    protected function chatResponse(array $result, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'session_id'         => $result['session_id'] ?? null,
            'content'            => $result['content'] ?? null,
            'model_used'         => $result['model_used'] ?? null,
            'processing_time_ms' => $result['processing_time_ms'] ?? 0,
            'success'            => $result['success'] ?? false,
        ], $extra), $result['success'] ? 200 : 500);
    }

    protected function validateAndGetSession(Request $request): ?JsonResponse
    {
        $this->limiter->check($request->user()->id);

        $request->validate([
            'message'    => 'required|string|max:2000',
            'session_id' => 'nullable|integer|exists:ai_chat_sessions,id',
        ]);

        return $this->validateSession($request, $request->input('session_id'));
    }
}
