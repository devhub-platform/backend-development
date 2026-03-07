<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class PostContentGeneratorService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri'        => rtrim(config('services.llama.base_url'), '/') . '/',
            'headers'         => [
                'x-rapidapi-key'  => config('services.llama.api_key'),
                'x-rapidapi-host' => config('services.llama.host'),
                'Content-Type'    => 'application/json',
            ],
            'connect_timeout' => 10,
            'timeout'         => 60,
        ]);
    }

    /**
     * Generate post content from a prompt.
     * Returns the generated text string only — never auto-saved.
     *
     * @throws \Exception on API failure.
     */
    public function generate(string $prompt): string
    {
        Log::info('Llama content generation request', ['prompt' => substr($prompt, 0, 100)]);

        try {
            $response = $this->client->post('chat_completions', [
                'json' => [
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => 'You are a professional technical writer for a developer platform called DevHub. Generate well-structured, engaging post content in Markdown format.',
                        ],
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $content = $data['choices'][0]['message']['content']
                ?? $data['choices'][0]['text']
                ?? null;

            if (!$content) {
                throw new \Exception('Llama response missing content field');
            }

            return (string) $content;

        } catch (ConnectException $e) {
            Log::error('Llama: connection failed', ['message' => $e->getMessage()]);
            throw new \Exception('Could not connect to content generation service.');

        } catch (GuzzleException $e) {
            $body = null;
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $body = $e->getResponse()->getBody()->getContents();
            }

            Log::error('Llama: API error', [
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'body'    => $body,
            ]);

            throw new \Exception('Content generation failed: ' . ($body ?? $e->getMessage()));
        }
    }
}
