<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Models\Question;
use App\Services\Chat\ChatRateLimiter;
use App\Services\Chat\QuestionChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionChatController extends BaseChatController
{
    public function __construct(
        protected QuestionChatService $service,
        ChatRateLimiter               $limiter,
    ) {
        parent::__construct($limiter);
    }

    public function chat(Request $request, Question $question): JsonResponse
    {
        $this->authorize('view', $question);

        if ($error = $this->validateAndGetSession($request)) {
            return $error;
        }

        $result = $this->service->handle(
            question:  $question,
            message:   $request->input('message'),
            sessionId: $request->input('session_id'),
            userId:    $request->user()->id,
        );

        return $this->chatResponse($result, ['question_id' => $question->id]);
    }
}
