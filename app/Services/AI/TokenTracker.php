<?php

namespace App\Services\AI;

class TokenTracker
{
    private const MODEL_COSTS = [
        'openai/gpt-5.1' => 0.03,
        'openai/gpt-5.1-mini' => 0.015,
        'qwen/qwen3-next-80b-a3b-instruct' => 0.025,
        'qwen/qwen3-mini' => 0.012,
        'deepseek/deepseek-v3.2-special' => 0.02,
        'deepseek-v3.2-mini' => 0.01,
        'google/gemini-3-pro-preview' => 0.018,
        'google/gemini-3-pro-mini' => 0.009,
        'x-ai/grok-4.1-fast' => 0.022,
        'x-ai/grok-4.1-mini' => 0.011,
    ];

    public function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4.2);
    }

    public function getModelCost(string $modelId): float
    {
        return self::MODEL_COSTS[$modelId] ?? 0.01;
    }

    public function shouldUseFallback(string $message, string $modelId): bool
    {
        $tokens = $this->estimateTokens($message);
        $costPer1K = $this->getModelCost($modelId);
        $cost = ($tokens / 1000) * $costPer1K;

        $isLongMessage = $tokens > 500;
        $isExpensive = $cost > 0.02;
        $isGeneralQuestion = $this->isGeneralQuestion($message);

        return $isLongMessage || $isExpensive || $isGeneralQuestion;
    }

    private function isGeneralQuestion(string $message): bool
    {
        $generalKeywords = ['explain', 'what is', 'how does', 'tell me about', 'describe', 'hello', 'hi', 'what are'];
        $messageLower = strtolower($message);

        foreach ($generalKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
