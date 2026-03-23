<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class HackAIEmbeddingService
{
    /**
     * @return array<int, array<int, float|int>>
     */
    public function embedBatch(array $inputs, ?string $model = null): array
    {
        $cleanInputs = collect($inputs)
            ->map(fn($value) => trim((string) $value))
            ->filter(fn(string $value) => $value !== '')
            ->values()
            ->all();

        if (empty($cleanInputs)) {
            return [];
        }

        $token = (string) config('services.hackai.token');
        if ($token === '') {
            throw new RuntimeException('HackAI API key is missing.');
        }

        $response = Http::baseUrl(rtrim((string) config('services.hackai.base_url'), '/'))
            ->withToken($token)
            ->acceptJson()
            ->timeout((int) config('services.hackai.embeddings_timeout', 20))
            ->post('embeddings', [
                'model' => $model ?: config('services.hackai.embeddings_model', 'openai/text-embedding-3-large'),
                'input' => $cleanInputs,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('HackAI embeddings request failed with status ' . $response->status() . '.');
        }

        $items = $response->json('data');
        if (!is_array($items)) {
            throw new RuntimeException('HackAI embeddings response is invalid.');
        }

        usort($items, fn(array $a, array $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        return array_values(array_map(
            fn(array $item) => is_array($item['embedding'] ?? null) ? $item['embedding'] : [],
            $items
        ));
    }
}

