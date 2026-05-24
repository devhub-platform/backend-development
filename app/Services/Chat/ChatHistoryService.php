<?php

namespace App\Services\Chat;

use App\Models\AIChatSession;
use App\Models\AIChatMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChatHistoryService
{
    // =========================================================================
    // Cache keys & TTLs
    // =========================================================================

    private const SESSIONS_KEY = 'chat:sessions:user:';
    private const SESSIONS_TTL = 300;  // 5 minutes

    private const MESSAGES_KEY = 'chat:messages:session:';
    private const MESSAGES_TTL = 600;  // 10 minutes

    private const SESSION_KEY  = 'chat:session:';
    private const SESSION_TTL  = 600;  // 10 minutes

    // =========================================================================
    // Session resolution
    // =========================================================================

    /**
     * Resolve existing session or create new one.
     * - If session_id provided and belongs to user → reuse it
     * - If model differs from session model → caller handles the mismatch
     * - If no session_id → create new with given model (or default)
     */
    public function resolveSession(?int $sessionId, ?string $model, ?int $userId): AIChatSession
    {
        $model = $model ?? config('ai_models.default');

        if ($sessionId && $userId) {
            $session = $this->getCachedSession($sessionId, $userId);

            if ($session) {
                // Remove updated_at — Laravel handles it automatically
                $session->update([
                    'active'    => true,
                    'closed_at' => null,
                ]);
                $this->bustSessionCache($sessionId, $userId);
                return $session->fresh();
            }
        }

        $session = AIChatSession::create([
            'user_id' => $userId,
            'title'   => $this->generateTitle($model),
            'model'   => $model,
            'active'  => true,
            'pinned'  => false,
        ]);

        $this->bustSessionsListCache($userId);

        return $session;
    }

    /**
     * Create a fresh session with given model (or default).
     * Used when user explicitly starts a new chat.
     */
    public function createSession(?string $model, int $userId): AIChatSession
    {
        $model = $model ?? config('ai_models.default');

        $session = AIChatSession::create([
            'user_id' => $userId,
            'title'   => $this->generateTitle($model),
            'model'   => $model,
            'active'  => true,
            'pinned'  => false,
        ]);

        $this->bustSessionsListCache($userId);

        return $session;
    }

    // =========================================================================
    // Message persistence
    // =========================================================================

    /**
     * Persist a user message.
     * If this is the first message in the session, use it as the session title.
     */
    public function storeUserMessage(int $sessionId, string $content, array $attachments = []): void
    {
        $isFirst = !AIChatMessage::where('ai_chat_session_id', $sessionId)->exists();

        AIChatMessage::create([
            'ai_chat_session_id' => $sessionId,
            'role'               => 'user',
            'content'            => $content,
            'attachments'        => $attachments,
        ]);

        $updates = [];

        // Use first message as session title (max 60 chars)
        if ($isFirst) {
            $trimmed          = trim($content);
            $updates['title'] = mb_substr($trimmed, 0, 60) . (mb_strlen($trimmed) > 60 ? '...' : '');
        }

        if (!empty($updates)) {
            AIChatSession::where('id', $sessionId)->update($updates);
        }

        // Bust caches
        $this->bustMessagesCache($sessionId);
        $session = AIChatSession::find($sessionId);
        if ($session) {
            $this->bustSessionCache($sessionId, $session->user_id);
            $this->bustSessionsListCache($session->user_id);
        }
    }

    public function storeAIMessage(int $sessionId, string $content): void
    {
        AIChatMessage::create([
            'ai_chat_session_id' => $sessionId,
            'role'               => 'assistant',
            'content'            => $content,
            'attachments'        => [],
        ]);

        $this->bustMessagesCache($sessionId);
    }

    // =========================================================================
    // Reads (cached)
    // =========================================================================

    public function getLastMessages(int $sessionId, int $limit = 12): array
    {
        return AIChatMessage::where('ai_chat_session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->take($limit)
            ->get(['role', 'content', 'attachments', 'created_at'])
            ->toArray();
    }

    /**
     * Return all messages for a session — cached for MESSAGES_TTL seconds.
     * Used by HistoryController::show().
     */
    public function getSessionMessages(int $sessionId): \Illuminate\Support\Collection
    {
        return Cache::remember(
            self::MESSAGES_KEY . $sessionId . ':all',
            self::MESSAGES_TTL,
            fn() => AIChatMessage::where('ai_chat_session_id', $sessionId)
                ->orderBy('created_at', 'asc')
                ->get()
        );
    }

    /**
     * Return all sessions for a user — cached for SESSIONS_TTL seconds.
     * Used by HistoryController::sessions().
     */
    public function getSessionsList(int $userId): \Illuminate\Support\Collection
    {
        return Cache::remember(
            self::SESSIONS_KEY . $userId,
            self::SESSIONS_TTL,
            fn() => AIChatSession::withCount('messages')
                ->where('user_id', $userId)
                ->orderBy('pinned', 'desc')
                ->orderBy('updated_at', 'desc')
                ->get()
        );
    }

    /**
     * Return a single session — cached for SESSION_TTL seconds.
     */
    public function getCachedSession(int $sessionId, int $userId): ?AIChatSession
    {
        $result = Cache::remember(
            self::SESSION_KEY . $sessionId . ':user:' . $userId,
            self::SESSION_TTL,
            fn() => AIChatSession::where('id', $sessionId)
                ->where('user_id', $userId)
                ->first()
        );

        return $result instanceof AIChatSession ? $result : null;
    }

    // =========================================================================
    // Mutations with cache invalidation
    // =========================================================================

    public function updateSessionTitle(int $sessionId, string $title): bool
    {
        $updated = (bool) AIChatSession::where('id', $sessionId)->update(['title' => $title]);

        if ($updated) {
            $session = AIChatSession::find($sessionId);
            if ($session) {
                $this->bustSessionCache($sessionId, $session->user_id);
                $this->bustSessionsListCache($session->user_id);
            }
        }

        return $updated;
    }

    public function pinSession(int $sessionId): void
    {
        $session = AIChatSession::find($sessionId);
        if ($session) {
            $session->update(['pinned' => true]);
            $this->bustSessionCache($sessionId, $session->user_id);
            $this->bustSessionsListCache($session->user_id);
        }
    }

    public function unpinSession(int $sessionId): void
    {
        $session = AIChatSession::find($sessionId);
        if ($session) {
            $session->update(['pinned' => false]);
            $this->bustSessionCache($sessionId, $session->user_id);
            $this->bustSessionsListCache($session->user_id);
        }
    }

    public function getSessionMessagesCount(int $sessionId): int
    {
        return AIChatMessage::where('ai_chat_session_id', $sessionId)->count();
    }

    public function cleanupOldSessions(int $days = 30): int
    {
        $date     = now()->subDays($days);
        $sessions = AIChatSession::where('updated_at', '<', $date)
            ->where('pinned', false)
            ->get();

        $deleted = 0;
        foreach ($sessions as $session) {
            AIChatMessage::where('ai_chat_session_id', $session->id)->delete();

            $this->bustMessagesCache($session->id);
            $this->bustSessionCache($session->id, $session->user_id);
            $this->bustSessionsListCache($session->user_id);
            Cache::forget("chat:ctx:{$session->id}");

            $session->delete();
            $deleted++;
        }

        return $deleted;
    }

    // =========================================================================
    // Cache bust helpers
    // =========================================================================

    public function bustMessagesCache(int $sessionId): void
    {
        Cache::forget(self::MESSAGES_KEY . $sessionId . ':all');
    }

    public function bustSessionCache(int $sessionId, int $userId): void
    {
        Cache::forget(self::SESSION_KEY . $sessionId . ':user:' . $userId);
    }

    public function bustSessionsListCache(int $userId): void
    {
        Cache::forget(self::SESSIONS_KEY . $userId);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

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
        return $knownModels[$lowerModel] ?? ucwords(preg_replace('/\s+/', ' ', trim($model))) ?: 'AI Chat';
    }
}
