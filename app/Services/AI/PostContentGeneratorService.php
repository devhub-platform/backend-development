<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PostContentGeneratorService
{
    private const CACHE_TTL = 3600 * 6;

    private const ENDPOINT = 'https://ai.hackclub.com/proxy/v1/chat/completions';

    private const MODEL = 'google/gemini-2.5-flash';

    private const LENGTH_CONFIG = [
        'short'  => [
            'tokens'      => 600,
            'instruction' => 'Write a short post (~300 words).',
        ],
        'medium' => [
            'tokens'      => 900,
            'instruction' => 'Write a medium-length post (~600 words).',
        ],
        'long'   => [
            'tokens'      => 1300,
            'instruction' => 'Write a detailed blog post of at least 1000 words. Include introduction, at least 5 sections, and a conclusion. Do not stop early.',
        ],
    ];

    // Keywords that indicate the user wants a title
    private const TITLE_KEYWORDS = [
        'title', 'تايتل', 'عنوان', 'with title', 'add title',
        'generate title', 'create title', 'make title',
    ];

    // Keywords that indicate the user wants title ONLY (no content)
    private const TITLE_ONLY_KEYWORDS = [
        'write a title', 'write title', 'generate a title', 'create a title',
        'titel',
        'title only', 'just title', 'only title', 'title for',
        'تايتل بس', 'عنوان بس', 'بس تايتل', 'بس عنوان',
    ];

    public function generate(string $prompt, string $length = 'medium', bool $forceTitle = false): array
    {
        $length    = in_array($length, ['short', 'medium', 'long']) ? $length : 'medium';
        $cacheKey  = 'post:gen:' . md5($prompt . $length . ($forceTitle ? '1' : '0'));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($prompt, $length, $forceTitle) {

            $result = ['content' => null, 'titles' => null];

            // Prompt explicitly asks for title — always title only
            // regardless of generate_title flag
            if ($this->promptWantsTitleOnly($prompt)) {
                $result['titles'] = $this->callTitleAI($prompt);
                return $result;
            }

            $wantsTitle = $forceTitle || $this->promptWantsTitle($prompt);

            // Generate content
            $config  = self::LENGTH_CONFIG[$length];
            $content = null;

            try {
                $content = $this->callAI($prompt, $config['tokens'], $config['instruction']);

                if (!$this->isLongEnough($content)) {
                    throw new \Exception('Generated content is too short');
                }

            } catch (\Exception $e) {
                Log::warning('PostContentGeneratorService: primary generation failed, retrying', [
                    'error' => $e->getMessage(),
                ]);

                $content = $this->callAI(
                    $prompt,
                    700,
                    'Write a complete and well-structured post with clear headings.'
                );
            }

            if ($length === 'long' && $this->needsExpansion($content)) {
                Log::info('PostContentGeneratorService: expanding long-form content');

                $extra   = $this->callAI(
                    "Continue the following post in detail:\n\n" . $content,
                    600,
                    'Continue writing the remaining sections in detail.'
                );
                $content .= "\n\n" . $extra;
            }

            $result['content'] = $content;

            // Generate title alongside content if requested
            if ($wantsTitle && !empty($content)) {
                try {
                    $result['titles'] = $this->callTitleAI($prompt);
                } catch (\Exception $e) {
                    Log::warning('PostContentGeneratorService: title generation failed', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $result;
        });
    }

    private function callAI(string $prompt, int $maxTokens, string $instruction): string
    {
        $response = Http::timeout(120)
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('services.hackai.token'),
            ])
            ->post(self::ENDPOINT, [
                'model'      => self::MODEL,
                'messages'   => [
                    ['role' => 'system', 'content' => $instruction],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'max_tokens' => $maxTokens,
            ]);

        if (!$response->successful()) {
            throw new \Exception('HackClub AI request failed: ' . $response->status());
        }

        return $response->json('choices.0.message.content') ?? '';
    }

    private function callTitleAI(string $prompt): array
    {
        $response = Http::timeout(60)
            ->withHeaders([
                'Authorization' => 'Bearer ' . config('services.hackai.token'),
            ])
            ->post(self::ENDPOINT, [
                'model'      => self::MODEL,
                'messages'   => [
                    ['role' => 'system', 'content' => 'Generate exactly 5 short SEO-friendly title options based on the user prompt. Return ONLY a JSON array of 5 strings, no numbering, no extra text. Example: ["Title One", "Title Two", "Title Three", "Title Four", "Title Five"]'],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'max_tokens' => 200,
            ]);

        $raw   = $response->json('choices.0.message.content') ?? '[]';
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $clean = preg_replace('/\s*```$/', '', $clean);

        $decoded = json_decode($clean, true);

        return is_array($decoded) ? array_slice($decoded, 0, 5) : [$raw];
    }

    private function promptWantsTitle(string $prompt): bool
    {
        $prompt = strtolower($prompt);

        foreach (self::TITLE_KEYWORDS as $keyword) {
            if (str_contains($prompt, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function promptWantsTitleOnly(string $prompt): bool
    {
        $prompt = strtolower($prompt);

        foreach (self::TITLE_ONLY_KEYWORDS as $keyword) {
            if (str_contains($prompt, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function isLongEnough(?string $content): bool
    {
        return $content && str_word_count($content) >= 500;
    }

    private function needsExpansion(string $content): bool
    {
        return str_word_count($content) < 900;
    }
}
