<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendEnrichmentService
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    private const TTL      = 21600; // 6 hours

    // ─── Public Entry Point ───────────────────────────────────────────────────

    public function enrich(array $items): array
    {
        if (empty($items)) return [];

        // ── Step 1: Separate cached vs pending (ALL items) ────────────────────
        $cached  = [];
        $pending = [];

        foreach ($items as $i => $item) {
            if ($cached_value = Cache::get($this->key($item))) {
                $cached[$i] = $cached_value;
            } else {
                $pending[$i] = $item;
            }
        }

        // ── Step 2: Single batch AI call for all pending items ────────────────
        $ai = $this->callBatch($pending);

        // ── Step 3: Apply AI/cache results to ALL items ───────────────────────
        foreach ($items as $i => &$item) {
            $r        = $cached[$i] ?? ($ai[$i] ?? []);
            $fallback = $this->fallback($item);

            if (!empty($r)) {
                Cache::put($this->key($item), $r, self::TTL);
            }

            $item['summary']      = (!empty($r['summary']))      ? $r['summary']      : $fallback['summary'];
            $item['why_trending'] = (!empty($r['why_trending'])) ? $r['why_trending'] : $fallback['why_trending'];
            $item['impact']       = (!empty($r['impact']))       ? $r['impact']       : $fallback['impact'];
        }
        unset($item);

        return $items;
    }

    // ─── Gemini Batch Call ────────────────────────────────────────────────────

    private function callBatch(array $items): array
    {
        if (empty($items)) return [];

        $payload = [];

        foreach ($items as $i => $item) {
            $payload[] = [
                'id'    => $i,
                'title' => $item['title']       ?? '',
                'desc'  => $item['description'] ?? '',
            ];
        }

        try {
            $requestBody = [
                'contents' => [[
                    'parts' => [[
                        'text' =>
                            "You are a tech trend analyst. Analyze each item and return ONLY a valid JSON array. " .
                            "No markdown, no explanation, no backticks. " .
                            "Format: [{\"id\": <int>, \"summary\": \"<2 sentences>\", \"why_trending\": \"<1 sentence>\", \"impact\": \"<1 sentence>\"}]\n\n" .
                            json_encode($payload, JSON_UNESCAPED_UNICODE),
                    ]]
                ]]
            ];

            Log::info('[Gemini] Sending batch request', [
                'item_count' => count($payload),
                'payload'    => $payload,
            ]);

            $res = Http::withHeaders([
                'X-goog-api-key' => config('services.gemini.api_key'),
                'Content-Type'   => 'application/json',
            ])->timeout(30)->retry(
                3,
                function (int $attemptNumber): int {
                    $waitMs = $attemptNumber * 2000; // 2s → 4s → 6s
                    Log::warning("[Gemini] Retry #{$attemptNumber}, waiting {$waitMs}ms");
                    return $waitMs;
                },
                function (\Throwable|\Illuminate\Http\Client\Response $responseOrException): bool {
                    // Retry on 503 HTTP response OR on connection exception
                    if ($responseOrException instanceof \Illuminate\Http\Client\Response) {
                        return $responseOrException->status() === 503;
                    }
                    return true; // retry on any exception (timeout, connection error)
                }
            )->post(self::ENDPOINT, $requestBody);

            Log::info('[Gemini] HTTP response', [
                'status' => $res->status(),
                'body'   => $res->body(),
            ]);

            if (!$res->successful()) {
                $status = $res->status();

                if ($status === 503) {
                    Log::warning('[Gemini] Service overloaded (503), all retries exhausted. Using fallback.');
                } else {
                    Log::error('[Gemini] Request failed', [
                        'status' => $status,
                        'body'   => $res->body(),
                    ]);
                }

                return [];
            }

            $raw = $res->json('candidates.0.content.parts.0.text');

            if (empty($raw)) {
                Log::warning('[Gemini] Empty response text', [
                    'full_response' => $res->json(),
                ]);
                return [];
            }

            return $this->parseGeminiResponse($raw);

        } catch (\Throwable $e) {
            Log::error('[Gemini] Exception during batch call', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

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
            Log::warning('[Gemini] Could not extract JSON array from response', ['raw' => $raw]);
            return [];
        }

        $decoded = json_decode($match[1], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('[Gemini] JSON decode error', [
                'error' => json_last_error_msg(),
                'json'  => $match[1],
            ]);
            return [];
        }

        if (!is_array($decoded)) {
            Log::warning('[Gemini] Decoded value is not an array', ['decoded' => $decoded]);
            return [];
        }

        $out = [];

        foreach ($decoded as $r) {
            $id = $r['id'] ?? null;

            if ($id === null) {
                Log::warning('[Gemini] Item missing id field', ['item' => $r]);
                continue;
            }

            $out[(int) $id] = [
                'summary'      => trim($r['summary']      ?? ''),
                'why_trending' => trim($r['why_trending'] ?? ''),
                'impact'       => trim($r['impact']       ?? ''),
            ];
        }

        Log::info('[Gemini] Parsed successfully', ['result_count' => count($out), 'out' => $out]);

        return $out;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function key(array $item): string
    {
        return 'trend:ai:' . md5(($item['source'] ?? '') . '|' . ($item['title'] ?? ''));
    }

    private function fallback(array $item): array
    {
        $title = $item['title'] ?? 'This item';

        return [
            'summary'      => "{$title} is currently trending in the tech community.",
            'why_trending' => 'High engagement from developers and tech enthusiasts.',
            'impact'       => 'Growing developer interest may accelerate adoption.',
        ];
    }
}
