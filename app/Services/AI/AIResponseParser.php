<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class AIResponseParser
{
    /**
     * Parse the raw API response and extract the text content.
     *
     * Handles multiple response formats across providers:
     *
     *  Standard OpenAI / HackAI (text):
     *    { choices: [{ message: { content: "Hello" } }] }
     *
     *  Vision / multimodal (content is an array of typed blocks):
     *    { choices: [{ message: { content: [{ type: "text", text: "Hello" }] } }] }
     *
     *  Legacy text completion:
     *    { choices: [{ text: "Hello" }] }
     *
     *  Simple wrappers:
     *    { response: "..." }
     *    { content: "..." }
     *    { data: [{ content: "..." }] }
     */
    public function parse(array $body): string
    {
        // ── Format 1: standard OpenAI chat completion ─────────────────────────
        if (isset($body['choices'][0]['message']['content'])) {
            $content = $body['choices'][0]['message']['content'];

            // Content is a plain string — most common case.
            if (is_string($content)) {
                $trimmed = trim($content);
                return $trimmed !== '' ? $trimmed : $this->empty($body);
            }

            // Content is an array of typed blocks (vision / multimodal response).
            // Extract all text blocks and join them.
            if (is_array($content)) {
                return $this->extractFromBlocks($content) ?: $this->empty($body);
            }

            // Content key exists but is null — model returned nothing (e.g. safety filter).
            return $this->empty($body);
        }

        // ── Format 2: legacy text completion ─────────────────────────────────
        if (isset($body['choices'][0]['text'])) {
            $trimmed = trim((string) $body['choices'][0]['text']);
            return $trimmed !== '' ? $trimmed : $this->empty($body);
        }

        // ── Format 3–5: simple wrapper formats ───────────────────────────────
        foreach (['response', 'message', 'content'] as $key) {
            if (isset($body[$key]) && is_string($body[$key])) {
                $trimmed = trim($body[$key]);
                if ($trimmed !== '') {
                    return $trimmed;
                }
            }
        }

        // ── Format 6: data array ──────────────────────────────────────────────
        if (isset($body['data'][0]['content']) && is_string($body['data'][0]['content'])) {
            $trimmed = trim($body['data'][0]['content']);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        // ── Unrecognised format — log for debugging ───────────────────────────
        return $this->empty($body);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Extract and join all text blocks from a multimodal content array.
     *
     * Vision responses look like:
     *   [
     *     { "type": "text",  "text": "The image shows..." },
     *     { "type": "image", ... },   ← skipped
     *   ]
     *
     * @param  array  $blocks
     * @return string  Joined text, or empty string if no text blocks found.
     */
    private function extractFromBlocks(array $blocks): string
    {
        $parts = [];

        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? '';

            if ($type === 'text' && isset($block['text']) && is_string($block['text'])) {
                $trimmed = trim($block['text']);
                if ($trimmed !== '') {
                    $parts[] = $trimmed;
                }
            }

            // Some providers use "content" instead of "text" inside the block.
            if ($type === 'text' && empty($parts) && isset($block['content']) && is_string($block['content'])) {
                $trimmed = trim($block['content']);
                if ($trimmed !== '') {
                    $parts[] = $trimmed;
                }
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Log the unrecognised body and return the fallback message.
     * Keeping the log here means the caller never has to think about it.
     */
    private function empty(array $body): string
    {
        Log::warning('AIResponseParser: unrecognised response format', [
            'keys'   => array_keys($body),
            // Log only the top-level structure to avoid dumping huge base64 blobs.
            'sample' => json_encode(array_slice($body, 0, 3)),
        ]);

        return 'I received an empty response. Please try again.';
    }
}
