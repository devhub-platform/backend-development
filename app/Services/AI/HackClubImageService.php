<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class HackClubImageService
{
    private Client $client;

    /**
     * Nanobanana is HackClub's image generation model.
     * It returns a base64-encoded PNG in the response body.
     */
    private const MODEL    = 'nanobanana';
    private const ENDPOINT = 'images/generations';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri'        => rtrim(config('services.hackClub.base_url', 'https://ai.hackclub.com/proxy/v1'), '/') . '/',
            'headers'         => [
                'Authorization' => 'Bearer ' . config('services.hackClub.api_key'),
                'Content-Type'  => 'application/json',
            ],
            'connect_timeout' => 10,
            'timeout'         => 60,
        ]);
    }

    /**
     * Generate an image from a text prompt.
     * Returns the raw base64 string (without the data URI prefix).
     *
     * @throws \Exception on API failure or unexpected response format.
     */
    public function generateBase64(string $prompt): string
    {
        Log::info('HackClub image generation request', ['prompt' => $prompt]);

        try {
            $response = $this->client->post(self::ENDPOINT, [
                'json' => [
                    'model'  => self::MODEL,
                    'prompt' => $prompt,
                    'n'      => 1,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Response format: { data: [{ b64_json: "..." }] }
            $base64 = $data['data'][0]['b64_json'] ?? null;

            if (!$base64) {
                throw new \Exception('HackClub image response missing b64_json field');
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
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'body'    => $body,
            ]);

            throw new \Exception('Image generation failed: ' . ($body ?? $e->getMessage()));
        }
    }
}
