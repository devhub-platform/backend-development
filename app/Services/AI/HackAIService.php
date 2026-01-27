<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HackAIService
{
    protected Client $client;
    protected array $cache = [];

    public function __construct()
    {
        \Log::info('Initializing HackAIService', [
            'base_url' => config('services.hackai.base_url'),
            'has_token' => !empty(config('services.hackai.token'))
        ]);

        $this->client = new Client([
            'base_uri' => config('services.hackai.base_url') . '/',
            'headers'  => [
                'Authorization' => 'Bearer ' . config('services.hackai.token'),
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 60,
            'connect_timeout' => 15,
        ]);
    }

    public function chat(array $messages, string $model, int $maxTokens = 500): array
    {
        \Log::info('HackAI chat called:', [
            'model' => $model,
            'messages_count' => count($messages),
            'max_tokens' => $maxTokens
        ]);

        $cacheKey = md5(serialize([$messages, $model, $maxTokens]));

        if (isset($this->cache[$cacheKey])) {
            \Log::info('Returning cached response');
            return $this->cache[$cacheKey];
        }

        try {
            \Log::info('Sending request to HackAI API...');

            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model'    => $model,
                    'messages' => $messages,
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.7,
                ],
            ]);

            \Log::info('HackAI API response status:', [
                'status' => $response->getStatusCode()
            ]);

            $data = json_decode($response->getBody(), true);

            if (is_array($data)) {
                \Log::info('HackAI response valid');
                $this->cache[$cacheKey] = $data;
                return $data;
            }

            \Log::warning('HackAI returned invalid response');
            return $this->getFallbackResponse();

        } catch (GuzzleException $e) {
            \Log::error('HackAI API Error:', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return $this->getFallbackResponse();
        }
    }

    private function getFallbackResponse(): array
    {
        \Log::warning('Returning fallback response from HackAIService');

        return [
            'choices' => [
                [
                    'message' => [
                        'content' => "I'm having trouble generating a response right now. Please try again."
                    ]
                ]
            ]
        ];
    }
}
