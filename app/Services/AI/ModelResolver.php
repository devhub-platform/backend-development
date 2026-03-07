<?php

namespace App\Services\AI;

class ModelResolver
{
    private array $chatModels;
    private array $imageModels;
    private TokenTracker $tracker;

    public function __construct(TokenTracker $tracker)
    {
        $this->chatModels  = config('ai_models.chat', []);
        $this->imageModels = config('ai_models.image', []);
        $this->tracker     = $tracker;
    }

    /**
     * Resolve which chat model to use.
     * Falls back to a cheaper model if the message is simple enough.
     */
    public function resolve(string $requestedModel, string $userMessage = ''): string
    {
        if (empty($userMessage)) {
            return $requestedModel;
        }

        foreach ($this->chatModels as $model) {
            if ($model['id'] === $requestedModel) {
                if ($this->tracker->shouldUseFallback($userMessage, $model['id']) && isset($model['fallback'])) {
                    return $model['fallback'];
                }
                return $model['id'];
            }
        }

        return config('ai_models.default', 'openai/gpt-5-mini');
    }

    /**
     * Resolve which image model to use based on prompt complexity.
     *
     * Simple prompts  → default (cheaper, faster)
     * Complex prompts → highest cost model (better quality)
     */
    public function resolveImageModel(string $prompt, ?string $requestedModel = null): string
    {
        // If caller passed a valid model, use it directly
        if ($requestedModel) {
            $exists = collect($this->imageModels)->firstWhere('id', $requestedModel);
            if ($exists) {
                return $requestedModel;
            }
        }

        $isComplex = $this->tracker->isComplexPrompt($prompt);

        if ($isComplex) {
            $premium = collect($this->imageModels)->sortByDesc('cost')->first();
            return $premium['id'] ?? $this->defaultImageModel();
        }

        return $this->defaultImageModel();
    }

    /** Return the default image model ID from config. */
    public function defaultImageModel(): string
    {
        $default = collect($this->imageModels)->firstWhere('default', true);
        return $default['id'] ?? 'google/gemini-2.5-flash-image';
    }

    /** Estimate cost for a given image model. */
    public function imageModelCost(string $modelId): float
    {
        $model = collect($this->imageModels)->firstWhere('id', $modelId);
        return $model['cost'] ?? 0.04;
    }
}
