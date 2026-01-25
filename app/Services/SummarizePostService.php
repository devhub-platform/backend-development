<?php

namespace App\Services;

use App\Enums\SUPPORTED_LANGUAGES;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SummarizePostService
{
    private const CACHE_DURATION = 60 * 60 * 24; // 24 hours
    private const MAX_TEXT_LENGTH = 50000;
    private const MIN_TEXT_LENGTH = 10;

    protected string $baseUrl = "https://article-extractor-and-summarizer.p.rapidapi.com/summarize-text";

    public function summarize(string $text, string $lang = 'en'): array
    {
        try {

            $validation = $this->validateInput($text, $lang);
            if (isset($validation['error'])) {
                return $validation;
            }

            $text = $validation['text'];

            $cacheKey = $this->generateCacheKey($text, $lang);

            if (Cache::has($cacheKey)) {
                Log::info("Summary retrieved from cache", [
                    'text_length' => strlen($text),
                    'language' => $lang,
                ]);

                return [
                    'summary' => Cache::get($cacheKey),
                    'cached' => true,
                ];
            }

            $summary = $this->callSummarizationAPI($text, $lang);

            if (isset($summary['error'])) {
                return $summary;
            }

            if ($summary['summary']) {
                Cache::put($cacheKey, $summary['summary'], self::CACHE_DURATION);
                Log::info("Summary cached successfully", [
                    'text_length' => strlen($text),
                    'language' => $lang,
                    'cache_duration' => self::CACHE_DURATION,
                ]);
            }

            return [
                'summary' => $summary['summary'],
                'cached' => false,
            ];
        } catch (\Exception $e) {
            Log::error("Summarization service exception", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [
                'error' => 'An error occurred during text summarization',
                'summary' => null,
                'cached' => false,
            ];
        }
    }

    private function validateInput(string $text, string $lang): array
    {

        $text = trim($text);

        if (empty($text)) {
            return [
                'error' => 'Text content cannot be empty',
            ];
        }

        if (strlen($text) < self::MIN_TEXT_LENGTH) {
            return [
                'error' => "Text must be at least " . self::MIN_TEXT_LENGTH . " characters long",
            ];
        }

        if (strlen($text) > self::MAX_TEXT_LENGTH) {
            return [
                'error' => "Text cannot exceed " . self::MAX_TEXT_LENGTH . " characters",
            ];
        }

        if (!in_array($lang, array_map(fn($case) => $case->value, SUPPORTED_LANGUAGES::cases()))) {
            return [
                'error' => "Unsupported language: {$lang}. Supported: " . implode(', ', array_map(fn($case) => $case->value, SUPPORTED_LANGUAGES::cases())),
            ];
        }

        return [
            'text' => $text,
        ];
    }

    private function generateCacheKey(string $text, string $lang): string
    {
        return 'summarize_' . $lang . '_' . hash('sha256', $text);
    }

    private function callSummarizationAPI(string $text, string $lang): array
    {
        try {
            $response = Http::withHeaders([
                "x-rapidapi-key" => config('services.summarize.api_key'),
                "x-rapidapi-host" => config('services.summarize.host'),
                "Content-Type" => "application/json",
                "Accept" => "application/json",
                "Content-Length" => strlen($text),
                "Host" => config('services.summarize.host'),
            ])->timeout(30)->post($this->baseUrl, [
                "text" => $text,
                "lang" => $lang,
            ]);

            if ($response->failed()) {
                Log::error("Summarization API request failed", [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [
                    'error' => "Summarization API returned status {$response->status()}",
                    'summary' => null,
                ];
            }

            $data = $response->json();

            if (!isset($data['summary'])) {
                Log::warning("No summary in API response", [
                    'response' => $data,
                ]);

                return [
                    'error' => 'API did not return a summary',
                    'summary' => null,
                ];
            }

            Log::info("Summary generated successfully", [
                'text_length' => strlen($text),
                'summary_length' => strlen($data['summary']),
                'language' => $lang,
            ]);

            return [
                'summary' => $data['summary'],
            ];
        } catch (\Exception $e) {
            Log::error("API call exception", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [
                'error' => 'Failed to connect to summarization service',
                'summary' => null,
            ];
        }
    }

    public function clearCache(string $text, string $lang): bool
    {
        $cacheKey = $this->generateCacheKey($text, $lang);
        return Cache::forget($cacheKey);
    }

    public function clearAllCache(): bool
    {
        try {
            $keys = Cache::getStore()->connection()->keys('summarize_*');
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            return true;
        } catch (\Exception $e) {
            Log::warning("Could not clear all summarization cache", [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public static function getSupportedLanguages(): array
    {
        return array_map(fn($case) => $case->value, SUPPORTED_LANGUAGES::cases());
    }

    public function getCacheStats(): array
    {
        return [
            'cache_duration' => self::CACHE_DURATION . ' seconds (' . (self::CACHE_DURATION / 3600) . ' hours)',
            'max_text_length' => self::MAX_TEXT_LENGTH . ' characters',
            'min_text_length' => self::MIN_TEXT_LENGTH . ' characters',
            'supported_languages' => self::getSupportedLanguages(),
        ];
    }
}
