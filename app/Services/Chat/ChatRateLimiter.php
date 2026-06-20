<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChatRateLimiter
{
    // 20 requests per minute per user
    private const MAX_ATTEMPTS = 20;
    private const DECAY_SECONDS = 60;

    public function check(?int $userId): void
    {
        if (!$userId) return;

        $executed = RateLimiter::attempt(
            key:          'chat:' . $userId,
            maxAttempts:  self::MAX_ATTEMPTS,
            callback:     fn() => true,
            decaySeconds: self::DECAY_SECONDS
        );

        if (!$executed) {
            $seconds = RateLimiter::availableIn('chat:' . $userId);

            throw new HttpResponseException(
                response()->json([
                    'error'       => 'Too many requests',
                    'retry_after' => $seconds,
                    'message'     => "Please wait {$seconds} seconds before sending another message.",
                ], 429)
            );
        }
    }
}
