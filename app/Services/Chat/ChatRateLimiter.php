<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChatRateLimiter
{
    public function check(?int $userId): void
    {
        if (!$userId) return;

        if (!RateLimiter::attempt('chat:' . $userId, 20, fn () => true)) {
            throw new HttpResponseException(
                response()->json(['error' => 'Too many requests'], 429)
            );
        }
    }
}
