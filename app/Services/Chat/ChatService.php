<?php

namespace App\Services\Chat;

use App\Services\AI\HackAIService;
use App\Services\AI\AIResponseParser;

class ChatService
{
    public function __construct(
        protected ChatHistoryService $history,
        protected HackAIService $ai,
        protected AIResponseParser $parser,
        protected ChatRateLimiter $limiter
    ) {}

    public function handle(array $data, $user): array
    {
        $this->limiter->check($user?->id);

        $session = $this->history->resolveSession(
            $data['session_id'] ?? null,
            $data['model'],
            $user?->id
        );

        $this->history->storeUserMessage(
            $session->id,
            $data['message'],
            $data['attachments'] ?? []
        );

        $messages = $this->history->getHistory($session->id);

        $raw = $this->ai->chat($messages, $session->model);

        $content = $this->parser->parse($raw);

        $this->history->storeAIMessage($session->id, $content);

        return [
            'session_id' => $session->id,
            'content'    => $content, // Markdown
        ];
    }
}
