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
            'base_uri'        => rtrim(config('services.hackai.base_url'), '/') . '/',
            'headers'         => [
                'Authorization' => 'Bearer ' . config('services.hackai.token'),
                'Content-Type'  => 'application/json',
            ],
            'timeout'         => 300,
            'connect_timeout' => 10,
            'read_timeout'    => 90,
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

            $data = json_decode($response->getBody()->getContents(), true);

            if (!is_array($data)) {
                throw new \Exception('Invalid AI response format — expected JSON object.');
            }

            // The content field can be either:
            //   - a string  → normal text response
            //   - an array  → vision / multimodal response with typed blocks
            //   - null      → model declined to respond (e.g. safety filter)
            // All three are valid — we only throw if the key is missing entirely.
            if (!array_key_exists('choices', $data) || empty($data['choices'])) {
                Log::error('HackAI: response has no choices', ['data' => $data]);
                throw new \Exception('AI response missing choices field.');
            }

            $choice  = $data['choices'][0] ?? null;
            $message = $choice['message'] ?? null;

            if ($message === null || !array_key_exists('content', $message)) {
                Log::error('HackAI: response missing message.content', ['data' => $data]);
                throw new \Exception('AI response missing content field.');
            }

            return $data;

        } catch (ConnectException $e) {
            Log::error('HackAI connection failed', [
                'message' => $e->getMessage(),
                'model'   => $model,
            ]);

            throw new \Exception('Connection to AI service failed: ' . $e->getMessage());

        } catch (GuzzleException $e) {
            // Attempt to extract the error body for better diagnostics.
            $errorBody = null;
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                try {
                    $errorBody = $e->getResponse()->getBody()->getContents();
                } catch (\Throwable) {
                    // Body already consumed — ignore
                }
            }

            Log::error('HackAI request failed', [
                'message' => $e->getMessage(),
                'model'   => $model,
                'body'    => $errorBody,
            ]);

            // Re-throw so ChatService::callWithRetry() can attempt a second call
            // before giving up. The controller catches the final exception and
            // returns a user-friendly error message.
            throw new \Exception(
                $errorBody
                    ? 'AI API error: ' . $errorBody
                    : 'AI request failed: ' . $e->getMessage()
            );
        }
    }
}
