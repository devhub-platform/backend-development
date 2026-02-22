<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class HackAIService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => rtrim(config('services.hackai.base_url'), '/') . '/',
            'headers'  => [
                'Authorization' => 'Bearer ' . config('services.hackai.token'),
                'Content-Type'  => 'application/json',
            ],
            'timeout'         => 300,
            'connect_timeout' => 60,
            'read_timeout'    => 300,
        ]);
    }

    public function chat(array $messages, string $model, int $maxTokens = 1000): array
    {
        set_time_limit(600);

        Log::info('HackAI chat request', [
            'model'          => $model,
            'messages_count' => count($messages),
            'max_tokens'     => $maxTokens,
        ]);

        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model'       => $model,
                    'messages'    => $messages,
                    'max_tokens'  => $maxTokens,
                    'temperature' => 0.7,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (is_array($data)) {
                return $data;
            }

            Log::warning('HackAI returned non-array response', ['model' => $model]);
            return $this->fallbackResponse();

        } catch (GuzzleException $e) {
            Log::error('HackAI API request failed', [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'model'   => $model,
            ]);
            return $this->fallbackResponse();
        }
    }

    private function fallbackResponse(): array
    {
        return [
            'choices' => [[
                'message' => [
                    'content' => "I'm having trouble generating a response right now. Please try again."
                ]
            ]]
        ];
    }
}
