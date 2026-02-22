<?php

namespace App\Services\Chat;

use App\Models\AIChatSession;
use App\Models\AIChatMessage;
use Illuminate\Support\Str;

class ChatHistoryService
{
    /**
     * Find an existing session for the user or create a new one.
     * If session_id is provided but belongs to a different user, a new session is created.
     */
    public function resolveSession(?int $sessionId, string $model, ?int $userId): AIChatSession
    {
        if ($sessionId && $userId) {
            $session = AIChatSession::where('id', $sessionId)
                ->where('user_id', $userId)
                ->first();

            if ($session) {
                $session->update([
                    'active'     => true,
                    'closed_at'  => null,
                    'updated_at' => now(),
                ]);
                return $session;
            }
        }

        return AIChatSession::create([
            'user_id' => $userId,
            'title'   => $this->generateTitle($model),
            'model'   => $model,
            'active'  => true,
            'pinned'  => false,
        ]);
    }

    public function storeUserMessage(int $sessionId, string $content, array $attachments = []): void
    {
        AIChatMessage::create([
            'ai_chat_session_id' => $sessionId,
            'role'               => 'user',
            'content'            => $content,
            'attachments'        => $attachments,
        ]);

        AIChatSession::where('id', $sessionId)->update(['updated_at' => now()]);
    }

    public function storeAIMessage(int $sessionId, string $content): void
    {
        AIChatMessage::create([
            'ai_chat_session_id' => $sessionId,
            'role'               => 'assistant',
            'content'            => $content,
            'attachments'        => [],
        ]);
    }

    // Limit matches ChatContextCache::$limit to keep DB and cache in sync
    public function getLastMessages(int $sessionId, int $limit = 12): array
    {
        return AIChatMessage::where('ai_chat_session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->take($limit)
            ->get(['role', 'content', 'attachments', 'created_at'])
            ->toArray();
    }

    public function updateSessionTitle(int $sessionId, string $title): bool
    {
        return (bool) AIChatSession::where('id', $sessionId)->update(['title' => $title]);
    }

    public function getSessionMessagesCount(int $sessionId): int
    {
        return AIChatMessage::where('ai_chat_session_id', $sessionId)->count();
    }

    /**
     * Delete unpinned sessions older than $days days (including their messages).
     */
    public function cleanupOldSessions(int $days = 30): int
    {
        $date     = now()->subDays($days);
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
            'gpt oss 120b'           => 'GPT OSS 120B',
            'gpt 5 mini'             => 'GPT-5 Mini',
            'qwen3 235b a22b'        => 'Qwen3 235B',
            'qwen3 32b'              => 'Qwen3 32B',
            'gemini 2 5 flash'       => 'Gemini 2.5 Flash',
            'deepseek v3 2 speciale' => 'DeepSeek V3.2',
            'deepseek v3 2'          => 'DeepSeek V3.2',
            'deepseek r1 0528'       => 'DeepSeek R1',
            'grok 4 1 fast'          => 'Grok 4.1',
            'kimi k2 thinking'       => 'Kimi K2',
        ];

        $lowerModel = strtolower(trim($model));
        if (isset($knownModels[$lowerModel])) {
            return $knownModels[$lowerModel];
        }

        return ucwords(preg_replace('/\s+/', ' ', trim($model))) ?: 'AI Chat';
    }
}
