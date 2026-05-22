<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InteractionLoggerService
{
    private string $baseUrl = 'https://memo1714-devhub-ai-api.hf.space';
    private string $endpoint = '/log_interaction';


    public function logInteraction(
        int $userId,
        string $articleUuid,
        string $category,
        string $action,
        int $duration = 0,
        array $additionalData = []
    ): bool {
        try {
            $payload = [
                'user_id' => (string)$userId,
                'article_uuid' => $articleUuid,
                'category' => $category,
                'action' => $action,
                'duration' => $duration,
                ...$additionalData
            ];

            $response = Http::timeout(10)
                ->post($this->baseUrl . $this->endpoint, $payload);

            if ($response->successful()) {
                Log::info('Interaction logged successfully', [
                    'user_id' => $userId,
                    'article_uuid' => $articleUuid,
                    'action' => $action
                ]);
                return true;
            }

            Log::warning('Failed to log interaction', [
                'user_id' => $userId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Exception while logging interaction', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function batchLogInteractions(array $interactions): bool
    {
        $success = true;
        foreach ($interactions as $interaction) {
            $logged = $this->logInteraction(
                $interaction['user_id'],
                $interaction['article_uuid'],
                $interaction['category'],
                $interaction['action'],
                $interaction['duration'] ?? 0,
                $interaction['additional_data'] ?? []
            );
            $success = $success && $logged;
        }
        return $success;
    }
}

