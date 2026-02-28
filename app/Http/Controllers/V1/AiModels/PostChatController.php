<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Models\Post;
use App\Services\Chat\ChatRateLimiter;
use App\Services\Chat\PostChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostChatController extends BaseChatController
{
    public function __construct(
        protected PostChatService $service,
        ChatRateLimiter           $limiter,
    ) {
        parent::__construct($limiter);
    }

    public function chat(Request $request, Post $post): JsonResponse
    {
        $this->authorize('view', $post);

        if ($error = $this->validateAndGetSession($request)) {
            return $error;
        }

        $result = $this->service->handle(
            post:      $post,
            message:   $request->input('message'),
            sessionId: $request->input('session_id'),
            userId:    $request->user()->id,
        );

        return $this->chatResponse($result, ['post_id' => $post->id]);
    }
}
