<?php

namespace App\Services\Chat;

use App\Models\UserPromptUsage;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Enforces per-user prompt quotas.
 *
 * Limits are read from config/ai_models.php so they can be adjusted without
 * a code deploy:
 *
 *   'prompt_limits' => [
 *       'daily'   => 50,
 *       'monthly' => 500,
 *   ],
 *
 * How it works
 * ────────────
 * A row in `user_prompt_usage` stores the running counters.  On each call to
 * check() the counters are reset if the day / month boundary has passed, then
 * compared against the configured limits.  On consume() the counters are
 * atomically incremented.
 *
 * A lightweight Cache layer sits in front of the DB to avoid a read on every
 * single message in high-traffic sessions; the TTL is kept short (5 min) so
 * a reset is never missed by more than 5 minutes.
 */
class PromptLimiter
{
    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Throw an HttpResponseException (429) if the user has hit their limit.
     * Call this BEFORE the AI request is made.
     */
    public function check(int $userId): void
    {
        $usage = $this->getUsage($userId);

        [$dailyLimit, $monthlyLimit] = $this->limits();

        if ($dailyLimit > 0 && $usage['daily_count'] >= $dailyLimit) {
            $this->abort(
                'Daily prompt limit reached',
                "You have used all {$dailyLimit} prompts for today. Your limit resets at midnight.",
                $dailyLimit,
                $usage['daily_count'],
                'daily'
            );
        }

        if ($monthlyLimit > 0 && $usage['monthly_count'] >= $monthlyLimit) {
            $this->abort(
                'Monthly prompt limit reached',
                "You have used all {$monthlyLimit} prompts this month. Your limit resets on the 1st.",
                $monthlyLimit,
                $usage['monthly_count'],
                'monthly'
            );
        }
    }

    /**
     * Increment the counters after a successful AI response.
     * Call this AFTER the response has been returned to the user.
     */
    public function consume(int $userId): void
    {
        $today     = now()->toDateString();
        $thisMonth = now()->format('Y-m-01');

        $record = UserPromptUsage::firstOrCreate(
            ['user_id' => $userId],
            [
                'daily_count'        => 0,
                'monthly_count'      => 0,
                'last_daily_reset'   => $today,
                'last_monthly_reset' => $today,
            ]
        );

        $record->update([
            'daily_count'        => $record->last_daily_reset->format('Y-m-d') === $today
                ? $record->daily_count + 1
                : 1,
            'monthly_count'      => $record->last_monthly_reset->format('Y-m-') === now()->format('Y-m-')
                ? $record->monthly_count + 1
                : 1,
            'last_daily_reset'   => $today,
            'last_monthly_reset' => $thisMonth,
        ]);

        // Bust the cache so the next check() reads fresh counts
        Cache::forget("prompt_usage:{$userId}");
    }

    /**
     * Return current usage stats for a user (useful for profile/dashboard API).
     *
     * @return array{ daily_count: int, monthly_count: int, daily_limit: int, monthly_limit: int }
     */
    public function stats(int $userId): array
    {
        $usage = $this->getUsage($userId);
        [$dailyLimit, $monthlyLimit] = $this->limits();

        return [
            'daily_count'    => $usage['daily_count'],
            'monthly_count'  => $usage['monthly_count'],
            'daily_limit'    => $dailyLimit,
            'monthly_limit'  => $monthlyLimit,
            'daily_remaining'   => max(0, $dailyLimit - $usage['daily_count']),
            'monthly_remaining' => max(0, $monthlyLimit - $usage['monthly_count']),
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /** Read (and auto-reset) usage, with a short cache to reduce DB hits. */
    private function getUsage(int $userId): array
    {
        return Cache::remember("prompt_usage:{$userId}", self::CACHE_TTL_SECONDS, function () use ($userId) {
            $record = UserPromptUsage::firstOrCreate(
                ['user_id' => $userId],
                [
                    'daily_count'        => 0,
                    'monthly_count'      => 0,
                    'last_daily_reset'   => now()->toDateString(),
                    'last_monthly_reset' => now()->toDateString(),
                ]
            );

            $today     = now()->toDateString();
            $thisMonth = now()->format('Y-m-01');

            $dailyCount   = $record->daily_count;
            $monthlyCount = $record->monthly_count;
            $needsUpdate  = false;

            if ($record->last_daily_reset->format('Y-m-d') !== $today) {
                $dailyCount  = 0;
                $needsUpdate = true;
            }

            if ($record->last_monthly_reset->format('Y-m-d') < $thisMonth) {
                $monthlyCount = 0;
                $needsUpdate  = true;
            }

            if ($needsUpdate) {
                $record->update([
                    'daily_count'        => $dailyCount,
                    'monthly_count'      => $monthlyCount,
                    'last_daily_reset'   => $today,
                    'last_monthly_reset' => $thisMonth,
                ]);
            }

            return [
                'daily_count'   => $dailyCount,
                'monthly_count' => $monthlyCount,
            ];
        });
    }

    /** [dailyLimit, monthlyLimit] — 0 means disabled */
    private function limits(): array
    {
        $cfg = config('ai_models.prompt_limits', []);

        return [
            (int) ($cfg['daily']   ?? 50),
            (int) ($cfg['monthly'] ?? 500),
        ];
    }

    private function abort(string $error, string $message, int $limit, int $used, string $period): never
    {
        throw new HttpResponseException(
            response()->json([
                'error'   => $error,
                'message' => $message,
                'limit'   => $limit,
                'used'    => $used,
                'period'  => $period,
            ], 429)
        );
    }
}
