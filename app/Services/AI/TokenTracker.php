<?php

namespace App\Services\AI;

class TokenTracker
{
    /**
     * Cost per 1K tokens — read from ai_models config so adding a new model
     * to the config automatically picks up its cost here too.
     * Falls back to the hardcoded map for models not in config.
     */
    private const FALLBACK_COSTS = [
        'openai/gpt-oss-120b'                          => 0.03,
        'openai/gpt-5-mini'                            => 0.015,
        'qwen/qwen3-235b-a22b'                         => 0.025,
        'qwen/qwen3-32b'                               => 0.012,
        'deepseek/deepseek-v3.2-speciale'              => 0.02,
        'deepseek/deepseek-v3.2'                       => 0.01,
        'deepseek/deepseek-r1-0528'                    => 0.02,
        'google/gemini-2.5-flash'                      => 0.018,
        'google/gemini-2.5-flash-lite-preview-09-2025' => 0.009,
        'x-ai/grok-4.1-fast'                           => 0.022,
        'moonshotai/kimi-k2-thinking'                  => 0.025,
        'qwen/qwen3-next-80b-a3b-instruct'             => 0.02,
    ];

    private const COMPLEX_KEYWORDS = [
        'implement', 'build', 'create', 'debug', 'fix', 'optimize',
        'analyze', 'compare', 'refactor', 'architecture', 'design',
        'algorithm', 'explain in detail', 'step by step',
    ];

    private const SIMPLE_KEYWORDS = [
        'hello', 'hi', 'hey', 'thanks', 'thank you', 'ok', 'okay',
        'what is', 'what are', 'tell me about', 'describe',
    ];

    // Image-specific complexity signals
    private const COMPLEX_IMAGE_KEYWORDS = [
        'detailed', 'realistic', 'high quality', 'professional',
        'cinematic', '4k', 'hd', 'ultra', 'photorealistic',
        'intricate', 'highly detailed', 'sharp focus',
    ];

    public function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4.2);
    }

    /**
     * Look up the cost per 1K tokens.
     * Checks ai_models config first, falls back to the hardcoded map.
     */
    public function getModelCost(string $modelId): float
    {
        $fromConfig = collect(config('ai_models.chat', []))
            ->firstWhere('id', $modelId);

        if ($fromConfig && isset($fromConfig['cost'])) {
            return (float) $fromConfig['cost'];
        }

        return self::FALLBACK_COSTS[$modelId] ?? 0.015;
    }

    public function shouldUseFallback(string $message, string $modelId): bool
    {
        if ($this->isComplexQuestion($message)) {
            return false;
        }

        $tokens    = $this->estimateTokens($message);
        $costPer1K = $this->getModelCost($modelId);
        $cost      = ($tokens / 1000) * $costPer1K;

        $isShortSimple   = $tokens < 100 && $this->isSimpleQuestion($message);
        $isVeryExpensive = $cost > 0.05;

        return $isShortSimple || $isVeryExpensive;
    }

    private function isComplexQuestion(string $message): bool
    {
        $lower = strtolower($message);
        foreach (self::COMPLEX_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if an image prompt is complex enough to warrant a premium model.
     * Used by ModelResolver::resolveImageModel().
     */
    public function isComplexPrompt(string $prompt): bool
    {
        if (strlen($prompt) > 150) {
            return true;
        }

        $lower = strtolower($prompt);
        foreach (self::COMPLEX_IMAGE_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function isSimpleQuestion(string $message): bool
    {
        $lower = strtolower($message);
        foreach (self::SIMPLE_KEYWORDS as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
