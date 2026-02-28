<?php

namespace App\Services\Chat;

use App\Models\AIChatMessage;
use App\Models\AIChatSession;
use Illuminate\Support\Str;

class ChatHistoryService
{
    /**
     * Resolve an existing session or create a new one.
     *
     * If a valid session ID is provided and belongs to the user, the session
     * is reactivated and returned. Otherwise a fresh session is created.
     */
    public function resolveSession(?int $sessionId, ?string $model, ?int $userId): AIChatSession
    {
        $model = $model ?? config('ai_models.default');

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

    /**
     * Create a brand-new session, ignoring any existing session context.
     * Used when the user explicitly starts a new chat.
     */
    public function createSession(?string $model, int $userId): AIChatSession
    {
        $model = $model ?? config('ai_models.default');

        return AIChatSession::create([
            'user_id' => $userId,
            'title'   => $this->generateTitle($model),
            'model'   => $model,
            'active'  => true,
            'pinned'  => false,
        ]);
    }

    /** Persist a user message and touch the session's updated_at timestamp. */
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

    /** Persist an assistant message with no attachments. */
    public function storeAIMessage(int $sessionId, string $content): void
    {
        AIChatMessage::create([
            'ai_chat_session_id' => $sessionId,
            'role'               => 'assistant',
            'content'            => $content,
            'attachments'        => [],
        ]);
    }

    /**
     * Return the most recent $limit messages in chronological order.
     *
     * Fetches descending (newest first) then reverses, ensuring the AI always
     * receives recent context rather than the oldest messages in the session.
     */
    public function getLastMessages(int $sessionId, int $limit = 12): array
    {
        return AIChatMessage::where('ai_chat_session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get(['role', 'content', 'attachments', 'created_at'])
            ->reverse()
            ->values()
            ->toArray();
    }

    /** Update the display title of a session. */
    public function updateSessionTitle(int $sessionId, string $title): bool
    {
        return (bool) AIChatSession::where('id', $sessionId)->update(['title' => $title]);
    }

    /** Return the total number of messages in a session. */
    public function getSessionMessagesCount(int $sessionId): int
    {
        return AIChatMessage::where('ai_chat_session_id', $sessionId)->count();
    }

    /**
     * Delete unpinned sessions that have not been updated within the given number of days.
     * Returns the count of deleted sessions.
     */
    public function cleanupOldSessions(int $days = 30): int
    {
        $cutoff   = now()->subDays($days);
        $sessions = AIChatSession::where('updated_at', '<', $cutoff)
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

    // -------------------------------------------------------------------------

    private function generateTitle(string $model): string
    {
        foreach (config('ai_models.chat', []) as $aiModel) {
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

        return $knownModels[$lowerModel]
            ?? ucwords(preg_replace('/\s+/', ' ', trim($model)))
            ?: 'AI Chat';
    }
}
