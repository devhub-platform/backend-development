<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Unified AI Content Service
 *
 * Single entry point for all LLM generation on DevHub.
 * Uses Hack Club AI (qwen/qwen3-32b) for all text generation.
 *
 * Config keys (config/services.php → .env):
 *   HACKAI_API_KEY   — Hack Club AI bearer token
 *   HACKAI_BASE_URL  — https://ai.hackclub.com/proxy/v1
 *
 * Design principles:
 *  - Every output is cached by a hash of its inputs → zero repeat API calls.
 *  - All inputs are truncated before sending → controlled token spend.
 *  - Structured JSON is requested via system prompt → parseable responses.
 *  - One internal call() method → all HTTP logic in one place.
 */
class AIContentService
{
    private const MODEL           = 'qwen/qwen3-32b';
    private const CACHE_TTL       = 3600 * 6;   // 6 hours
    private const MAX_PROMPT      = 800;         // chars — balances quality vs cost
    private const MAX_TOKENS_POST = 2000;        // enough for a full blog post
    private const MAX_TOKENS_JSON = 600;         // enough for structured JSON responses

    // ─── Public Methods ───────────────────────────────────────────────────────

    /**
     * Generate a full Markdown post from a user prompt.
     *
     * Returns raw Markdown string. Never auto-saved to DB.
     * Cached by prompt hash → repeated identical prompts cost nothing.
     * Uses MAX_TOKENS_POST (2000) to allow full-length blog posts.
     */
    public function generatePost(string $prompt): string
    {
        $prompt   = $this->truncate($prompt, self::MAX_PROMPT);
        $cacheKey = 'ai:post:' . md5($prompt);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($prompt) {
            $system = <<<SYSTEM
You are a professional technical writer for DevHub, a developer community platform.
Generate well-structured, engaging post content in Markdown format.
Use clear headings, concise paragraphs, and relevant code examples where appropriate.
Respond with ONLY the Markdown content — no preamble, no commentary, no markdown fences.
SYSTEM;

            $raw = $this->call($system, $prompt, self::MAX_TOKENS_POST);

            // Strip ```markdown or ``` fences the model adds despite instructions
            $clean = preg_replace('/^```(?:markdown)?\s*/i', '', trim($raw));
            $clean = preg_replace('/\s*```$/', '', $clean);

            return trim($clean);
        });
    }

    /**
     * Generate a structured trending explanation for a single feed item.
     *
     * Returns a decoded array:
     *   [ 'summary' => '', 'why_trending' => '', 'impact' => '' ]
     *
     * Falls back to template values if JSON parsing fails so the feed
     * never breaks due to a malformed AI response.
     *
     * Cached by a hash of title + source + stats — changes only when the
     * item's engagement level changes meaningfully.
     * Uses MAX_TOKENS_JSON (600) — JSON responses are always short.
     */
    public function generateTrendExplanation(array $item): array
    {
        $title  = $this->truncate($item['title']       ?? 'Untitled', 200);
        $source = $item['source']  ?? 'unknown';
        $stats  = $item['stats']   ?? 0;
        $tags   = implode(', ', array_slice($item['tags'] ?? [], 0, 5));
        $desc   = $this->truncate($item['description'] ?? '', 300);

        // Cache key is based on title + source + stats only.
        // Description and tags are intentionally excluded — minor wording
        // changes shouldn't invalidate a perfectly good cached explanation.
        $cacheKey = 'ai:trend:' . md5("{$title}{$source}{$stats}");

        $raw = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($title, $source, $stats, $tags, $desc) {
            $system = <<<SYSTEM
You are a tech trend analyst. Given a trending item from the developer community,
return ONLY a valid JSON object with exactly these three keys:
  "summary"      — one sentence describing what this item is (max 25 words)
  "why_trending" — one sentence explaining why it is trending right now (max 25 words)
  "impact"       — one sentence on its potential impact on developers (max 25 words)
No markdown fences, no extra keys, no commentary. Pure JSON only.
SYSTEM;

            $user = "Title: {$title}\nSource: {$source}\nEngagement: {$stats}\nTags: {$tags}\nDescription: {$desc}";

            return $this->call($system, $user);  // uses MAX_TOKENS_JSON by default
        });

        return $this->parseJson($raw, [
            'summary'      => "Trending {$source} content gaining developer attention.",
            'why_trending' => 'Strong engagement from the developer community.',
            'impact'       => 'Could influence developers working with related technologies.',
        ]);
    }

    // ─── Internal HTTP Wrapper ────────────────────────────────────────────────

    /**
     * Core HTTP wrapper — all LLM calls pass through here.
     *
     * Reads base URL and API key from config/services.php which maps to:
     *   HACKAI_BASE_URL  → e.g. https://ai.hackclub.com/proxy/v1
     *   HACKAI_API_KEY   → Bearer token (stored as 'token' in services.php)
     *
     * $maxTokens defaults to MAX_TOKENS_JSON (600) for JSON responses.
     * Pass MAX_TOKENS_POST (2000) explicitly for full blog post generation.
     *
     * Returns the raw content string.
     * Throws RuntimeException on non-2xx so callers can decide to re-throw or fall back.
     */
    private function call(string $systemPrompt, string $userPrompt, int $maxTokens = self::MAX_TOKENS_JSON): string
    {
        $baseUrl = rtrim(config('services.hackai.base_url'), '/');

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . config('services.hackai.token'),
        ])
            ->timeout(30)
            ->retry(2, 500, fn($e) => !($e instanceof \Illuminate\Http\Client\ConnectionException))
            ->post("{$baseUrl}/chat/completions", [
                'model'      => self::MODEL,
                'max_tokens' => $maxTokens,
                'messages'   => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
            ]);

        if (!$response->successful()) {
            Log::error('AIContentService: API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('AI API returned ' . $response->status());
        }

        // OpenAI-compatible response shape: choices[0].message.content
        $content = $response->json('choices.0.message.content')
            ?? $response->json('choices.0.text');

        if (empty($content)) {
            throw new \RuntimeException('AI API returned empty content');
        }

        return trim($content);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Truncate to $max chars after collapsing whitespace.
     * Keeps token budget predictable regardless of input source.
     */
    private function truncate(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max) : $text;
    }

    /**
     * Safely decode a JSON string returned by the LLM.
     *
     * Strips markdown fences the model occasionally adds despite instructions.
     * Merges with $fallback so missing keys never cause undefined index errors.
     * Returns $fallback entirely on any parse failure.
     */
    private function parseJson(string $raw, array $fallback): array
    {
        // Strip optional ```json … ``` fences the model sometimes adds
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $clean = preg_replace('/\s*```$/', '', $clean);

        $decoded = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            Log::warning('AIContentService: JSON parse failed', ['raw' => substr($raw, 0, 300)]);
            return $fallback;
        }

        return array_merge($fallback, $decoded);
    }
}
