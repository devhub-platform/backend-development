<?php

namespace App\Services\Chat;

use App\Models\AIChatSession;
use App\Models\AIChatMessage;
use Exception;

class ChatHistoryService
{
    public function resolveSession(?int $sessionId, string $model, ?int $userId): AIChatSession
    {
        if ($sessionId) {
            $session = AIChatSession::findOrFail($sessionId);

            if ($session->model !== $model) {
                throw new Exception('Model mismatch. Create a new chat.');
            }

            return $session;
        }

        return AIChatSession::create([
            'user_id' => $userId,
            'model'   => $model,
        ]);
    }

    public function storeUserMessage(int $sessionId, string $content, array $attachments = []): void
    {
        AIChatMessage::create([
            'ai_chat_session_id' => $sessionId,
            'role'        => 'user',
            'content'     => $content,
            'attachments' => $attachments,
        ]);
    }

    public function storeAIMessage(int $sessionId, string $content): void
    {
        AIChatMessage::create([
            'ai_chat_session_id' => $sessionId,
            'role'    => 'assistant',
            'content' => $content,
            'attachments' => [],
        ]);
    }

    public function getHistory(int $sessionId): array
    {
        return AIChatMessage::where('ai_chat_session_id', $sessionId)
            ->orderBy('id')
            ->get(['role', 'content'])
            ->toArray();
    }
}
