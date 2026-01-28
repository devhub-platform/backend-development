<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Exception;

class HackAIChatService
{
    protected Client $client;

    public static array $availableModels = [
        ['model' => 'openai/gpt-5.1', 'title' => 'General Chat'],
        ['model' => 'deepseek/deepseek-v3.2-special', 'title' => 'Deep Knowledge'],
        ['model' => 'google/gemini-3-pro-preview', 'title' => 'Complex Reasoning'],
        ['model' => 'x-ai/grok-4.1-fast', 'title' => 'Fast Knowledge'],
        ['model' => 'qwen/qwen3-next-80b-a3b-instruct', 'title' => 'Coding'],
    ];

    public function __construct()
    {
        $baseUrl = rtrim(config('services.hackai.base_url', ''), '/');

        if (!$baseUrl) {
            throw new Exception('HackAI base URL not configured');
        }

        $this->client = new Client([
            'base_uri' => $baseUrl . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.hackai.token'),
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 60,
        ]);
    }

    public function chat(array $messages, string $model, bool $stream = false): ResponseInterface
    {
        try {
            return $this->client->post('chat/completions', [
                'json' => [
                    'model'    => $model,
                    'messages' => $messages,
                    'stream'   => $stream,
                ],
                'stream' => $stream,
            ]);
        } catch (GuzzleException $e) {
            throw new HttpResponseException(response()->json([
                'error'   => 'Failed to reach HackAI API',
                'message' => $e->getMessage(),
            ], 500));
        }
    }

    public function getAvailableModels(): array
    {
        return self::$availableModels;
    }

    public function isValidModel(string $model): bool
    {
        return collect(self::$availableModels)
            ->pluck('model')
            ->contains($model);
    }
}
