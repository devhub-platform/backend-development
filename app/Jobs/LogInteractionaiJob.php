<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogInteractionaiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $userId,
        private readonly string $category,
    )
    {
    }

    public function handle(): void
    {
        Http::timeout(5)->post('https://memo1714-devhub-ai-api.hf.space/log_interaction', [
            'user_id' => $this->userId,
            'article_uuid' => null,
            'category' => $this->category,
            'action' => 'view',
            'duration' => 50,
        ]);
    }
}
