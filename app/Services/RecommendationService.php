<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    private string $baseUrl = 'https://memo1714-devhub-ai-api.hf.space';
    private string $endpoint = '/recommendations';

    public function fetchRemoteRecommendations(?int $userId = null, array $interests = []): array
    {
        try {
            $query = [];
            if ($userId) {
                $query['user_id'] = (string)$userId;
            }
            if (!empty($interests)) {
                $query['interests'] = implode(',', $interests);
            }

            $response = Http::timeout(6)->get($this->baseUrl . $this->endpoint, $query);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::warning('RecommendationService: remote request failed', ['status' => $response->status(), 'body' => $response->body()]);
            return [];
        } catch (\Exception $e) {
            Log::error('RecommendationService exception: ' . $e->getMessage());
            return [];
        }
    }

    public function topCategoriesFromResponse(array $remoteResponse, int $limit = 3): array
    {
        $spectrum = $remoteResponse['user_vector_spectrum'] ?? [];
        if (!is_array($spectrum) || empty($spectrum)) {
            return [];
        }

        arsort($spectrum);
        return array_slice(array_keys($spectrum), 0, $limit);
    }
}

