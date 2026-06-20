<?php

namespace App\Jobs;

use App\Services\Posts\HomeFeedService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * FIX #1 — keeps the per-user interest vector warm in cache so that
 * HomeFeedService::build() never blocks on the embedding API at request time.
 *
 * Dispatched automatically from HomeFeedService::weightedInterestMap()
 * whenever that cache entry is rebuilt (every 5 minutes per user).
 *
 * Can also be dispatched manually, e.g. on login or after a user follows
 * new tags:
 *
 *   dispatch(new RefreshUserInterestVectorJob($user->id, $weightedMap));
 */
class RefreshUserInterestVectorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retry up to 3 times with exponential back-off.
     * If the embedding API is down the feed gracefully falls back to
     * keyword scoring, so failures here are non-critical.
     */
    public int $tries = 3;

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function __construct(
        public readonly int   $userId,
        public readonly array $weightedMap,
    ) {}

    public function handle(HomeFeedService $feedService): void
    {
        $feedService->buildAndCacheInterestVector($this->userId, $this->weightedMap);
    }
}
