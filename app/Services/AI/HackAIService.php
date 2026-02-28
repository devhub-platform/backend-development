<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class HackAIService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri'        => rtrim(config('services.hackai.base_url'), '/') . '/',
            'headers'         => [
                'Authorization' => 'Bearer ' . config('services.hackai.token'),
                'Content-Type'  => 'application/json',
            ],
            'timeout'         => 90,  // max wait for full response
            'connect_timeout' => 10,  // max wait to establish connection
            'read_timeout'    => 90,  // max wait for reading response
        ]);
    }

    public function chat(array $messages, string $model, int $maxTokens = 1000): array
    {
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

        } catch (ConnectException $e) {
            Log::error('HackAI connection failed', [
                'message' => $e->getMessage(),
                'model'   => $model,
            ]);
            return $this->fallbackResponse('Connection to AI service failed. Please try again.');

        } catch (GuzzleException $e) {
            Log::error('HackAI API request failed', [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
                'model'   => $model,
            ]);
            return $this->fallbackResponse();
        }
    }

    private function fallbackResponse(string $message = "I'm having trouble generating a response right now. Please try again."): array
    {
        return [
            'choices' => [[
                'message' => ['content' => $message]
            ]]
        ];
    }
}
