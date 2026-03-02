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
        if (function_exists('set_time_limit')) {
            set_time_limit(500);
        }
        $this->client = new Client([
            'base_uri' => rtrim(config('services.hackai.base_url'), '/') . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.hackai.token'),
                'Content-Type' => 'application/json',
            ],
            'timeout' => 300,
            'connect_timeout' => 10,
            'read_timeout' => 90,
        ]);
    }

    public function chat(array $messages, string $model, int $maxTokens = 1000): array
    {
        Log::info('HackAI chat request', [
            'model' => $model,
            'messages_count' => count($messages),
            'max_tokens' => $maxTokens,
        ]);

        try {

            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.7,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!is_array($data)) {
                throw new \Exception('Invalid AI response format');
            }

            if (!isset($data['choices'][0]['message']['content'])) {
                throw new \Exception('AI response missing content field');
            }

            return $data;

        } catch (ConnectException $e) {

            Log::error('HackAI connection failed', [
                'message' => $e->getMessage(),
                'model' => $model,
            ]);

            throw new \Exception('Connection to AI service failed: ' . $e->getMessage());

        } catch (GuzzleException $e) {

            /*
            $errorBody = null;

            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $errorBody = $e->getResponse()->getBody()->getContents();
            }

            Log::error('HackAI API request failed', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'model' => $model,
                'body' => $errorBody,
            ]);

            throw new \Exception(
                $errorBody
                    ? 'AI API Error: ' . $errorBody
                    : 'AI API request failed: ' . $e->getMessage()
            );
            */

            // Log full real exception
            Log::error('HackAI failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'model' => $model,
            ]);

            // Return safe fallback to user
            return [
                'success' => false,
                'fallback_message' => '⚠️ The AI service is currently unavailable. Please try again later.',
            ];
        }
    }
}
