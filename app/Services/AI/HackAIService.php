<?php

namespace App\Services\AI;

use GuzzleHttp\Client;

class HackAIService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('services.hackai.base_url') . '/',
            'headers'  => [
                'Authorization' => 'Bearer ' . config('services.hackai.token'),
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 60,
        ]);
    }

    public function chat(array $messages, string $model): array
    {
        $response = $this->client->post('chat/completions', [
            'json' => [
                'model'    => $model,
                'messages' => $messages,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}
