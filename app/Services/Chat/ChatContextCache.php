<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;

class ChatContextCache
{
    protected int $limit = 6;

    public function get(int $sessionId): array
    {
        return Cache::get("chat:ctx:{$sessionId}", []);
    }

    public function push(int $sessionId, array $message): void
    {
        $context = $this->get($sessionId);
        $context[] = $message;

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
