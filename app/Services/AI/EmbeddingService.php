<?php

namespace App\Services\AI;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    public function getCachedEmbedding(Post $post): array
    {
        if (empty($post->embedding)) return [];

        $decoded = $post->embedding;

        if (is_string($decoded)) {
            try {
                $decoded = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                return [];
            }
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function embed(string $text): array
    {
        $text = trim(mb_substr($text, 0, 500));

        if ($text === '') return [];

        return Cache::remember(
            'emb:' . md5($text),
            now()->addDays(7),
            fn() => $this->callApi($text)
        );
    }

    public function embedPost(Post $post): array
    {
        $existing = $this->getCachedEmbedding($post);

        if (!empty($existing) && is_array($existing)) {
            return $existing;
        }

        $vector = $this->embed($post->title . ' ' . ($post->content ?? ''));

        if (!empty($vector)) {
            $post->embedding = $vector;
            $post->embedded_at = now();
            $post->save();
        }

        return $vector;
    }

    private function callApi(string $text): array
    {
        try {
            $res = Http::timeout(25)    // increased from 10 → 25 seconds
            ->retry(2, 500)         // increased from retry(1, 200) → retry(2, 500)
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('services.embedding.key'),
            ])
                ->post(config('services.embedding.base_url') . '/embeddings', [
                    'model' => config('services.embedding.model'),
                    'input' => $text,
                ]);

            if (!$res->successful()) {
                Log::error('[Embedding] API error', [
                    'status' => $res->status(),
                    'body'   => $res->body(),
                ]);
                return [];
            }

            return $res->json('data.0.embedding') ?? [];

        } catch (\Throwable $e) {
            Log::error('[Embedding] Exception', [
                'msg' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function cosine(array $a, array $b): float
    {
        if (empty($a) || empty($b)) return 0;

        if (count($a) !== count($b)) return 0;

        $dot = $normA = $normB = 0;

        foreach ($a as $i => $v) {
            $bVal   = $b[$i] ?? 0;
            $dot   += $v * $bVal;
            $normA += $v * $v;
            $normB += $bVal * $bVal;
        }

        return ($normA > 0 && $normB > 0)
            ? $dot / (sqrt($normA) * sqrt($normB))
            : 0;
    }

    public function deduplicate(array $items, float $threshold = 0.88): array
    {
        $kept = [];

        foreach ($items as $item) {

            $vec = $item['embedding'] ?? [];

            if (empty($vec)) {
                $kept[] = $item;
                continue;
            }

            $isDup = false;

            foreach ($kept as $k) {
                if (
                    !empty($k['embedding']) &&
                    $this->cosine($vec, $k['embedding']) >= $threshold
                ) {
                    $isDup = true;
                    break;
                }
            }

            if (!$isDup) {
                $kept[] = $item;
            }
        }

        return $kept;
    }
}
