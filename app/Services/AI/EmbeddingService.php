<?php

namespace App\Services\AI;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EmbeddingService
 *
 * Generates and manages text embedding vectors using Qwen3-Embedding-8B.
 *
 * Storage hierarchy (fastest → slowest, checked in order):
 *   1. Redis cache  — md5-keyed, 24h TTL, always checked first
 *   2. DB column    — posts.embedding JSON column (permanent)
 *   3. API call     — last resort; result is written to both DB and cache
 *
 * This means any given title is embedded at most ONCE in its lifetime.
 */
class EmbeddingService
{
    private const API_URL   = 'https://ai.hackclub.com/proxy/v1/embeddings';
    private const MODEL     = 'qwen/qwen3-embedding-8b';
    private const CACHE_TTL = 86400;   // 24 hours
    private const MAX_CHARS = 500;     // token cost control
    private const TIMEOUT   = 8;

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Return the embedding vector for any text string.
     *
     * Uses Redis cache only — suitable for tech-trend items, user topics,
     * and any text that doesn't map to a DB row.
     *
     * Returns [] on failure so callers can safely degrade.
     */
    public function embed(string $text): array
    {
        $text     = $this->truncate($text);
        $cacheKey = 'emb:' . md5($text);

        return Cache::remember($cacheKey, self::CACHE_TTL, fn() => $this->callApi($text));
    }

    /**
     * Return the embedding for a Post model.
     *
     * Check order:
     *   1. Redis (fast path, 24h TTL)
     *   2. DB column posts.embedding (permanent store)
     *   3. API call → writes back to both DB and Redis
     *
     * WHY: Ensures a post's embedding is generated ONCE and reused forever,
     * even after cache eviction, because it persists in the DB.
     */
    public function embedPost(Post $post): array
    {
        $cacheKey = 'emb:post:' . $post->id;

        // 1. Redis hit
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey, []);
        }

        // 2. DB hit
        if (!empty($post->embedding)) {
            $vector = is_array($post->embedding)
                ? $post->embedding
                : json_decode($post->embedding, true);

            if (!empty($vector)) {
                Cache::put($cacheKey, $vector, self::CACHE_TTL);
                return $vector;
            }
        }

        // 3. API call (last resort)
        $text   = $this->truncate($post->title . ' ' . ($post->content ?? ''));
        $vector = $this->callApi($text);

        if (!empty($vector)) {
            // Persist to DB so this never hits the API again after cache eviction
            $post->updateQuietly(['embedding' => $vector]);
            Cache::put($cacheKey, $vector, self::CACHE_TTL);
        }

        return $vector;
    }

    /**
     * Cosine similarity between two vectors.
     * Returns 0.0 when either vector is empty (safe fallback).
     */
    public function cosine(array $a, array $b): float
    {
        if (empty($a) || empty($b)) {
            return 0.0;
        }

        $dot = $normA = $normB = 0.0;
        $len = min(count($a), count($b));

        for ($i = 0; $i < $len; $i++) {
            $dot   += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        $denom = sqrt($normA) * sqrt($normB);
        return $denom > 0.0 ? (float) ($dot / $denom) : 0.0;
    }

    /**
     * Deduplicate an array of items using cosine similarity on their titles.
     *
     * WHY: O(n²) comparisons, but n ≤ 15 in practice (3 sources × 5 items),
     * so at most 105 comparisons — negligible CPU cost.
     * All embed() calls are cached, so API cost is near-zero after warm-up.
     *
     * @param  array  $items      Each item must have a 'title' key.
     * @param  float  $threshold  Similarity above this = duplicate. Default 0.88.
     */
    public function deduplicate(array $items, float $threshold = 0.88): array
    {
        $embeddings = array_map(fn($item) => $this->embed($item['title'] ?? ''), $items);
        $kept       = [];

        foreach ($items as $i => $item) {
            $duplicate = false;

            foreach ($kept as $keptIdx) {
                if (empty($embeddings[$i]) || empty($embeddings[$keptIdx])) {
                    continue;
                }
                if ($this->cosine($embeddings[$i], $embeddings[$keptIdx]) >= $threshold) {
                    $duplicate = true;
                    break;
                }
            }

            if (!$duplicate) {
                $kept[] = $i;
            }
        }

        return array_values(array_intersect_key($items, array_flip($kept)));
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private function truncate(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        return mb_strlen($text) > self::MAX_CHARS
            ? mb_substr($text, 0, self::MAX_CHARS)
            : $text;
    }

    private function callApi(string $text): array
    {
        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(self::TIMEOUT)
                ->post(self::API_URL, [
                    'model' => self::MODEL,
                    'input' => $text,
                ]);

            if (!$response->successful()) {
                Log::warning('EmbeddingService: non-2xx response', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            return $response->json('data.0.embedding', []);

        } catch (\Exception $e) {
            Log::error('EmbeddingService: API call failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
