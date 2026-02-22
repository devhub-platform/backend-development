<?php

namespace App\Services\AI;

class TokenTracker
{
    // Cost per 1K tokens for each model (used to decide fallback)
    private const MODEL_COSTS = [
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

    // Messages that signal a complex task — always use the primary model
    private const COMPLEX_KEYWORDS = [
        'implement', 'build', 'create', 'debug', 'fix', 'optimize',
        'analyze', 'compare', 'refactor', 'architecture', 'design',
        'algorithm', 'explain in detail', 'step by step',
    ];

    // Messages that signal a simple query — fallback is sufficient
    private const SIMPLE_KEYWORDS = [
        'hello', 'hi', 'hey', 'thanks', 'thank you', 'ok', 'okay',
        'what is', 'what are', 'tell me about', 'describe',
    ];

    public function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4.2);
    }

    public function getModelCost(string $modelId): float
    {
        return self::MODEL_COSTS[$modelId] ?? 0.015;
    }

    public function shouldUseFallback(string $message, string $modelId): bool
    {
        // Never downgrade complex requests to fallback
        if ($this->isComplexQuestion($message)) {
            return false;
        }

        $tokens     = $this->estimateTokens($message);
        $costPer1K  = $this->getModelCost($modelId);
        $cost       = ($tokens / 1000) * $costPer1K;

        // Use fallback for short simple greetings or very expensive requests
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
