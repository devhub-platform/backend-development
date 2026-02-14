<?php

namespace App\Jobs;

use App\Models\PostView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TrackPostViewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $userId,
        private int $postId
    ) {
    }


    public function handle(): void
    {
        try {
            PostView::updateOrCreate(
                ['user_id' => $this->userId, 'post_id' => $this->postId],
                ['viewed_at' => now()]
            );
        } catch (\Exception $e) {
            Log::error('Failed to track post view', [
                'user_id' => $this->userId,
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

