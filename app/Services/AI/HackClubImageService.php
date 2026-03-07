<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class HackClubImageService
{
    private Client $client;

    public function __construct(
        private ModelResolver $resolver,
    ) {
        $this->client = new Client([
            'base_uri'        => rtrim(config('services.hackai.base_url', 'https://ai.hackclub.com/proxy/v1'), '/') . '/',
            'headers'         => [
                'Authorization' => 'Bearer ' . config('services.hackai.token'),
                'Content-Type'  => 'application/json',
            ],
            'connect_timeout' => 15,
            'timeout'         => 120,
        ]);
    }

    public function generateBase64(string $prompt, ?string $model = null): string
    {
        set_time_limit(0);

        $model = $this->resolver->resolveImageModel($prompt, $model);

        Log::info('HackClub image generation request', [
            'model'  => $model,
            'prompt' => $prompt,
        ]);

        try {
            $response = $this->client->post('images/generations', [
                'json' => [
                    'model'  => $model,
                    'prompt' => $prompt,
                    'n'      => 1,
                ],
            ]);

            $raw  = $response->getBody()->getContents();
            $data = json_decode($raw, true);

            Log::info('HackClub image generation raw response', [
                'model' => $model,
                'data'  => $data,
            ]);

            $item = $data['data'][0] ?? null;

            if (!$item) {
                throw new \Exception('Image response missing data field. Raw: ' . $raw);
            }

            // b64_json — most models
            if (!empty($item['b64_json'])) {
                return $item['b64_json'];
            }

            // url — some models return a link or data URI
            if (!empty($item['url'])) {
                return $this->resolveUrl($item['url']);
            }

            throw new \Exception('Image response missing both b64_json and url. Raw: ' . $raw);

        } catch (ConnectException $e) {
            Log::error('HackClub image: connection failed', ['message' => $e->getMessage()]);
            throw new \Exception('Could not connect to image generation service.');

        } catch (GuzzleException $e) {
            $body = null;
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $body = $e->getResponse()->getBody()->getContents();
            }

            Log::error('HackClub image: API error', [
                'model'   => $model,
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'body'    => $body,
            ]);

            throw new \Exception('Image generation failed: ' . ($body ?? $e->getMessage()));
        }
    }

    /**
     * Resolve a URL or data URI to a raw base64 string.
     */
    private function resolveUrl(string $url): string
    {
        // data:image/png;base64,<data>
        if (str_starts_with($url, 'data:')) {
            $comma = strpos($url, ',');
            if ($comma !== false) {
                return substr($url, $comma + 1);
            }
        }

        // Remote URL — download and encode
        $content = @file_get_contents($url);
        if ($content === false) {
            throw new \Exception('Failed to download image from URL: ' . $url);
        }

        return base64_encode($content);
    }
}
