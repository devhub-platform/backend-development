<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use App\Models\AIChatSession;
use App\Models\Attachment;
use App\Services\Chat\ChatHistoryService;
use App\Services\Chat\ChatRateLimiter;
use App\Services\Chat\ChatService;
use App\Services\Chat\PromptLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIChatController extends Controller
{
    public function __construct(
        protected ChatService        $chat,
        protected ChatRateLimiter    $rateLimiter,
        protected ChatHistoryService $history,
        protected PromptLimiter      $promptLimiter,
    ) {}

    public function chat(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // 1. Hard rate-limit (requests / minute) — prevents burst abuse
        $this->rateLimiter->check($userId);

        // 2. Soft quota check (daily / monthly prompts)
        $this->promptLimiter->check($userId);

        $validated = $request->validate([
            'session_id'    => 'nullable|exists:ai_chat_sessions,id',
            'model'         => 'nullable|string',
            'message'       => 'required|string|max:1500',
            'attachments'   => 'nullable|array|max:5',
            'attachments.*' => 'integer|exists:attachments,id',
        ]);

        // Security: session must belong to this user
        if (!empty($validated['session_id'])) {
            $session = AIChatSession::where('id', $validated['session_id'])
                ->where('user_id', $userId)
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found.',
                ], 404);
            }
        }

        // Security: attachments must belong to this user
        if (!empty($validated['attachments'])) {
            $validCount = Attachment::whereIn('id', $validated['attachments'])
                ->where('user_id', $userId)
                ->count();

            if ($validCount !== count($validated['attachments'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more attachments are invalid.',
                ], 403);
            }
        }

        $response = $this->chat->handle([
            'session_id'  => $validated['session_id'] ?? null,
            'model'       => $validated['model'] ?? null,
            'message'     => $validated['message'],
            'attachments' => $validated['attachments'] ?? [],
        ], $request->user());

        // Model mismatch → tell frontend to open new chat
        if (isset($response['error']) && $response['error'] === 'model_mismatch') {
            return response()->json($response, 409);
        }

        // Only charge a prompt against the quota when the AI actually responded
        if ($response['success'] ?? false) {
            $this->promptLimiter->consume($userId);
        }

        return response()->json([
            'session_id'         => $response['session_id'] ?? null,
            'ai_message'         => $response['content'] ?? 'No response',
            'model_used'         => $response['model_used'] ?? config('ai_models.default'),
            'processing_time_ms' => $response['processing_time_ms'] ?? 0,
            'success'            => $response['success'] ?? false,
        ]);
    }

    public function models(): JsonResponse
    {
        return response()->json([
            'default' => config('ai_models.default'),
            'models'  => config('ai_models.chat'),
        ]);
    }

    /**
     * Return the current user's prompt usage stats.
     * Useful for a "X of Y prompts used today" indicator in the frontend.
     *
     * GET /api/v1/ai/prompts/usage
     */
    public function promptUsage(Request $request): JsonResponse
    {
        return response()->json(
            $this->promptLimiter->stats($request->user()->id)
        );
    }
}
