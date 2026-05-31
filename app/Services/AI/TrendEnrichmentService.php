<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendEnrichmentService
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    private const TTL      = 21600; // 6 hours

    // ─── On-Demand Single Item Enrichment ────────────────────────────────────

    /**
     * Called when user opens a trend detail page.
     * Checks cache first — if miss, calls Gemini and caches result.
     */
    public function enrichSingle(array $item): array
    {
        $cacheKey = $this->getKey($item);
        $cached   = Cache::get($cacheKey);

        if ($cached) {
            return array_merge($item, $cached, ['ai_ready' => true]);
        }

        $results = $this->callBatch([$item]);
        $r       = $results[0] ?? [];

        Log::info('[Gemini] enrichSingle result', ['r' => $r, 'results' => $results]);

        if (!empty($r)) {
            Cache::put($cacheKey, $r, self::TTL);
            return array_merge($item, $r, ['ai_ready' => true]);
        }

        return array_merge($item, $this->getFallback($item), ['ai_ready' => false]);
    }

    /**
     * Batch enrichment — used by cron to pre-warm top items.
     */
    public function enrichBatch(array $items): array
    {
        if (empty($items)) return [];

        $cached  = [];
        $pending = [];

        foreach ($items as $i => $item) {
            $hit = Cache::get($this->getKey($item));
            if ($hit) {
                $cached[$i] = $hit;
            } else {
                $pending[$i] = $item;
            }
        }

        $ai = $this->callBatch($pending);

        foreach ($items as $i => &$item) {
            $r        = $cached[$i] ?? ($ai[$i] ?? []);
            $fallback = $this->getFallback($item);

            if (!empty($r)) {
                Cache::put($this->getKey($item), $r, self::TTL);
            }

            $item['summary']      = !empty($r['summary'])      ? $r['summary']      : $fallback['summary'];
            $item['why_trending'] = !empty($r['why_trending'])  ? $r['why_trending'] : $fallback['why_trending'];
            $item['impact']       = !empty($r['impact'])        ? $r['impact']       : $fallback['impact'];
            $item['ai_ready']     = !empty($r);
        }
        unset($item);

        return $items;
    }

    // ─── Gemini Batch Call ────────────────────────────────────────────────────

    private function callBatch(array $items): array
    {
        if (empty($items)) return [];

        $payload = [];
        $indexMap = [];
        $i = 0;

        foreach ($items as $originalIndex => $item) {
            $payload[]         = ['id' => $i, 'title' => $item['title'] ?? '', 'desc' => $item['description'] ?? ''];
            $indexMap[$i]      = $originalIndex;
            $i++;
        }

        try {
            $requestBody = [
                'contents' => [[
                    'parts' => [[
                        'text' =>
                            "You are a senior tech trend analyst writing for experienced developers. " .
                            "Analyze each trending tech item and return ONLY a valid JSON array. " .
                            "No markdown, no explanation, no backticks. Be specific, insightful, and technical. Avoid generic phrases like 'high engagement' or 'growing interest'. " .
                            "Format: [{\"id\": <int>, \"summary\": \"<3 sentences explaining what it is and why it matters technically>\", \"why_trending\": \"<1 specific sentence about what triggered the current spike in interest>\", \"impact\": \"<1 sentence about concrete technical or industry impact>\"}]\n\n" .
                            json_encode($payload, JSON_UNESCAPED_UNICODE),
                    ]]
                ]]
            ];

            Log::info('[Gemini] Sending batch request', ['item_count' => count($payload)]);

            $res = Http::withHeaders([
                'X-goog-api-key' => config('services.gemini.api_key'),
                'Content-Type'   => 'application/json',
            ])->timeout(30)->retry(
                3,
                fn(int $attempt): int => $attempt * 2000,
                function (\Throwable|\Illuminate\Http\Client\Response $e): bool {
                    if ($e instanceof \Illuminate\Http\Client\Response) {
                        return in_array($e->status(), [429, 503]);
                    }
                    return true;
                }
            )->post(self::ENDPOINT, $requestBody);

            if (!$res->successful()) {
                Log::error('[Gemini] Request failed', ['status' => $res->status()]);
                return [];
            }

            $raw = $res->json('candidates.0.content.parts.0.text');

            Log::info('[Gemini] Raw response', ['raw' => $raw, 'status' => $res->status()]);

            if (empty($raw)) {
                Log::warning('[Gemini] Empty response text');
                return [];
            }

            $parsed = $this->parseGeminiResponse($raw);

            // Re-map sequential ids back to original indexes
            $out = [];
            foreach ($parsed as $seqId => $data) {
                $originalIndex       = $indexMap[$seqId] ?? $seqId;
                $out[$originalIndex] = $data;
            }

            return $out;

        } catch (\Throwable $e) {
            Log::error('[Gemini] Exception', ['message' => $e->getMessage()]);
            return [];
        }
    }

    // ─── Response Parser ──────────────────────────────────────────────────────

    private function parseGeminiResponse(string $raw): array
    {
        $clean = trim($raw);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);
        $clean = trim($clean);

        if (!preg_match('/(\[[\s\S]*\])/u', $clean, $match)) {
            Log::warning('[Gemini] Could not extract JSON array', ['raw' => $raw]);
            return [];
        }

        $decoded = json_decode($match[1], true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            Log::warning('[Gemini] JSON decode error', ['error' => json_last_error_msg()]);
            return [];
        }

        $out = [];
        foreach ($decoded as $r) {
            $id = $r['id'] ?? null;
            if ($id === null) continue;

            $out[(int) $id] = [
                'summary'      => trim($r['summary']      ?? ''),
                'why_trending' => trim($r['why_trending'] ?? ''),
                'impact'       => trim($r['impact']       ?? ''),
            ];
        }

        return $out;
    }

    // ─── Public Helpers ───────────────────────────────────────────────────────

    public function getKey(array $item): string
    {
        return 'trend:ai:' . md5(($item['source'] ?? '') . '|' . ($item['title'] ?? ''));
    }

    public function getFallback(array $item): array
    {
        $title = $item['title'] ?? 'This item';

        return [
            'summary'      => "{$title} is currently trending in the tech community.",
            'why_trending' => 'High engagement from developers and tech enthusiasts.',
            'impact'       => 'Growing developer interest may accelerate adoption.',
        ];
    }
}
