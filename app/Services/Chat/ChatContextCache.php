<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;

class ChatContextCache
{
    // 12 slots = 6 user + 6 assistant message pairs
    protected int $limit = 12;

    public function get(int $sessionId): array
    {
        return Cache::get("chat:ctx:{$sessionId}", []);
    }

    public function push(int $sessionId, array $message): void
    {
        $context   = $this->get($sessionId);
        $context[] = $message;

        // Keep only the most recent messages within the limit
        if (count($context) > $this->limit) {
            $context = array_slice($context, -$this->limit);
        }

        Cache::put("chat:ctx:{$sessionId}", $context, now()->addMinutes(30));
    }

    public function clear(int $sessionId): void
    {
        Cache::forget("chat:ctx:{$sessionId}");
    }
}
