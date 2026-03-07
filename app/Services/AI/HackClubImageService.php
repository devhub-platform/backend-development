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
            'timeout'         => 300,
            'connect_timeout' => 15,
            'read_timeout'    => 180,
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

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info('HackClub RAW image response', [
                'model'    => $model,
                'response' => $data,
            ]);

            $base64 = null;

            // FORMAT 1: OpenAI style — data[0].b64_json
            if (isset($data['data'][0]['b64_json'])) {
                $base64 = $data['data'][0]['b64_json'];
            }

            // FORMAT 2: data[0].image_base64
            if (!$base64 && isset($data['data'][0]['image_base64'])) {
                $base64 = $data['data'][0]['image_base64'];
            }

            // FORMAT 3: data[0].b64
            if (!$base64 && isset($data['data'][0]['b64'])) {
                $base64 = $data['data'][0]['b64'];
            }

            // FORMAT 4: data[0].url
            if (!$base64 && isset($data['data'][0]['url'])) {
                $base64 = $this->urlToBase64($data['data'][0]['url']);
            }

            // FORMAT 5: Gemini / Chat style
            if (!$base64 && isset($data['choices'][0]['message']['images'][0]['image_url']['url'])) {
                $base64 = $this->urlToBase64($data['choices'][0]['message']['images'][0]['image_url']['url']);
            }

            if (!$base64) {
                Log::error('HackClub image generation: unsupported response format', [
                    'response' => $data,
                ]);
                throw new \Exception('Image response format not supported');
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

    /**
     * Convert URL or data URI to base64.
     */
    private function urlToBase64(string $url): string
    {
        if (str_starts_with($url, 'data:image')) {
            return explode(',', $url)[1];
        }

        $image = @file_get_contents($url);

        if (!$image) {
            throw new \Exception('Failed to download generated image');
        }

        return base64_encode($image);
    }
}
