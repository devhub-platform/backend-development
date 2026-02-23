<?php

namespace App\Services\AI;

class ModelResolver
{
    private array $models;
    private TokenTracker $tracker;

    public function __construct(TokenTracker $tracker)
    {
        $this->models  = config('ai_models.chat');
        $this->tracker = $tracker;
    }

    /**
     * Resolve which model to actually use.
     * Returns the fallback model if the message is simple/cheap enough.
     */
    public function resolve(string $requestedModel, string $userMessage = ''): string
    {
        if (empty($userMessage)) {
            return $requestedModel;
        }

        foreach ($this->models as $model) {
            if ($model['id'] === $requestedModel) {
                if ($this->tracker->shouldUseFallback($userMessage, $model['id']) && isset($model['fallback'])) {
                    return $model['fallback'];
                }
                return $model['id'];
            }
        }

        // Default fallback if the requested model is not in config
        return 'openai/gpt-5-mini';
    }
}
