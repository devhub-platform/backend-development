<?php

namespace App\Jobs;

use App\Services\InteractionLoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogInteractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $userId,
        private string $articleUuid,
        private string $category,
        private string $action,
        private int $duration = 0,
        private array $additionalData = [],
        private string $tags = ''
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(InteractionLoggerService $interactionLogger): void
    {
        $categoryToUse = !empty($this->tags) ? $this->tags : $this->category;

        $interactionLogger->logInteraction(
            $this->userId,
            $this->articleUuid,
            $categoryToUse,
            $this->action,
            $this->duration,
            $this->additionalData
        );
    }
}

