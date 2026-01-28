<?php

namespace App\Services\Chat;

use App\Models\AIChatSession;
use App\Models\AIChatMessage;
use Illuminate\Support\Str;

class ChatHistoryService
{
    public function resolveSession(?int $sessionId, string $model, ?int $userId): AIChatSession
    {
        if ($sessionId) {
            $session = AIChatSession::find($sessionId);

            if ($session && $session->user_id === $userId) {
                $session->update([
                    'active' => true,
                    'closed_at' => null,
                    'updated_at' => now()
                ]);

                return $session;
            }
        }

        return AIChatSession::create([
            'user_id' => $userId,
            'title' => $this->generateTitle($model),
            'model' => $model,
            'active' => true,
            'pinned' => false
        ]);
    }

    public function storeUserMessage(int $sessionId, string $content, array $attachments = []): void
    {
        AIChatMessage::create([
            'ai_chat_session_id' => $sessionId,
            'role' => 'user',
            'content' => $content,
            'attachments' => $attachments
        ]);

        AIChatSession::where('id', $sessionId)->update(['updated_at' => now()]);
    }

    public function storeAIMessage(int $sessionId, string $content): void
    {
        AIChatMessage::create([
            'ai_chat_session_id' => $sessionId,
            'role' => 'assistant',
            'content' => $content,
            'attachments' => []
        ]);
    }

    public function getLastMessages(int $sessionId, int $limit = 50): array
    {
        return AIChatMessage::where('ai_chat_session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->take($limit)
            ->get(['role', 'content', 'attachments', 'created_at'])
            ->toArray();
    }

    private function generateTitle(string $model): string
    {
        $aiModels = config('ai_models.chat', []);

        foreach ($aiModels as $aiModel) {
            if ($aiModel['id'] === $model && isset($aiModel['title'])) {
                return $aiModel['title'] . ' - ' . date('M d, H:i');
            }
        }

        return $this->formatModelName($model) . ' - ' . date('M d, H:i');
    }

    private function formatModelName(string $model): string
    {
        $model = Str::afterLast($model, '/');
        $model = str_replace(['_', '-', '.'], ' ', $model);

        $knownModels = [
            'gpt 5 1 mini' => 'GPT-5.1 Mini',
            'gpt 5 1' => 'GPT-5.1',
            'gpt 3 5 turbo' => 'GPT-3.5 Turbo',
            'qwen3 32b' => 'Qwen3 32B',
            'qwen3 72b' => 'Qwen3 72B',
            'gemini 3 pro' => 'Gemini 3 Pro',
            'deepseek v3 2' => 'DeepSeek V3.2',
            'grok 4 1' => 'Grok 4.1'
        ];

        $lowerModel = strtolower($model);
        if (isset($knownModels[$lowerModel])) {
            return $knownModels[$lowerModel];
        }

        $model = trim($model);
        $model = preg_replace('/\s+/', ' ', $model);
        $model = ucwords($model);

        return $model ?: 'AI Chat';
    }

    public function updateSessionTitle(int $sessionId, string $title): bool
    {
        return AIChatSession::where('id', $sessionId)->update(['title' => $title]);
    }

    public function getSessionMessagesCount(int $sessionId): int
    {
        return AIChatMessage::where('ai_chat_session_id', $sessionId)->count();
    }

    public function cleanupOldSessions(int $days = 30): int
    {
        $date = now()->subDays($days);

        $sessions = AIChatSession::where('updated_at', '<', $date)
            ->where('pinned', false)
            ->get();

        $deleted = 0;

        foreach ($sessions as $session) {
            AIChatMessage::where('ai_chat_session_id', $session->id)->delete();
            $session->delete();
            $deleted++;
        }

        return $deleted;
    }
}
