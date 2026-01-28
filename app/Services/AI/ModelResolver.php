<?php

namespace App\Services\AI;

class ModelResolver
{
    private array $models;
    private TokenTracker $tracker;

    public function __construct(TokenTracker $tracker)
    {
        $this->models = config('ai_models.chat');
        $this->tracker = $tracker;
    }

    public function resolve(string $requestedModel, string $userMessage = ''): string
    {
        if (empty($userMessage)) {
            return $requestedModel;
        }

        foreach ($this->models as $model) {
            if ($model['id'] === $requestedModel) {
                $useFallback = $this->tracker->shouldUseFallback($userMessage, $model['id']);
                if ($useFallback && isset($model['fallback'])) {
                    return $model['fallback'];
                }
                return $model['id'];
            }
        }

        return 'openai/gpt-5.1-mini';
    }
}
