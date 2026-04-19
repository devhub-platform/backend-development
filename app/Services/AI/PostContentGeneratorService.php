<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class PostContentGeneratorService
{
    public function __construct(
        private AIContentService $ai,
    ) {}

    /**
     * Generate post content from a prompt.
     * Delegates to AIContentService (Hack Club AI / qwen3-32b).
     * Returns the generated Markdown string only — never auto-saved.
     *
     * @throws \Exception on API failure.
     */
    public function generate(string $prompt): string
    {
        Log::info('PostContentGeneratorService: generation request', [
            'prompt' => substr($prompt, 0, 100),
        ]);

        try {
            return $this->ai->generatePost($prompt);
        } catch (\Exception $e) {
            Log::error('PostContentGeneratorService: generation failed', [
                'message' => $e->getMessage(),
            ]);
            throw new \Exception('Content generation failed: ' . $e->getMessage());
        }
    }
}
