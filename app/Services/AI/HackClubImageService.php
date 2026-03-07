<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class HackClubImageService
{
    private Client $client;

    private const ENDPOINT = 'images/generations';

    public function __construct(
        private ModelResolver $resolver,
    ) {
        if (function_exists('set_time_limit')) {
            set_time_limit(500);
        }
        $this->client = new Client([
            'base_uri'        => rtrim(config('services.hackai.base_url', 'https://ai.hackclub.com/proxy/v1'), '/') . '/',
            'headers'         => [
                'Authorization' => 'Bearer ' . config('services.hackai.token'),
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 300,
            'connect_timeout' => 10,
            'read_timeout' => 90,
        ]);
    }

    /**
     * Generate an image from a text prompt.
     * Model is resolved automatically based on prompt complexity unless explicitly passed.
     * Returns the raw base64 string (without the data URI prefix).
     *
     * @throws \Exception on API failure or unexpected response format.
     */
    public function generateBase64(string $prompt, ?string $model = null): string
    {
        $model = $this->resolver->resolveImageModel($prompt, $model);

        Log::info('HackClub image generation request', [
            'model'  => $model,
            'prompt' => $prompt,
            'cost'   => $this->resolver->imageModelCost($model),
        ]);

        try {
            $response = $this->client->post(self::ENDPOINT, [
                'json' => [
                    'model'  => $model,
                    'prompt' => $prompt,
                    'n'      => 1,
                ],
            ]);

            $data   = json_decode($response->getBody()->getContents(), true);
            $base64 = $data['data'][0]['b64_json'] ?? null;

            Log::info('HackClub image generation response', [
                'model' => $model,
                'data'  => isset($data['data'][0]) ? 'b64_json present' : 'b64_json missing',
            ]);

            if (!$base64) {
                throw new \Exception('Image response missing b64_json field');
            }

            return $base64;

        } catch (ConnectException $e) {
            Log::error('HackClub image generation: connection failed', [
                'message' => $e->getMessage(),
            ]);
            throw new \Exception('Could not connect to image generation service.');

        } catch (GuzzleException $e) {
            $body = null;
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $body = $e->getResponse()->getBody()->getContents();
            }

            Log::error('HackClub image generation: API error', [
                'model'   => $model,
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'body'    => $body,
            ]);

            throw new \Exception('Image generation failed: ' . ($body ?? $e->getMessage()));
        }
    }
}
