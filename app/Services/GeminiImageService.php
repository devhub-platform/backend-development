<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GeminiImageService
{
    protected string $apiKey;
    protected string $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent";

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Generate an image using Gemini API.
     */
    public function generateImage(string $prompt, string $filename = null): string
    {
        $response = Http::withHeaders([
            "x-goog-api-key" => $this->apiKey,
            "Content-Type" => "application/json",
        ])->post($this->endpoint, [
            "contents" => [[
                "parts" => [
                    ["text" => $prompt]
                ]
            ]]
        ]);

        $json = $response->json();

        if (!$response->successful()) {
            throw new \Exception("Gemini API error: " . json_encode($json));
        }

        $base64 = collect($json['candidates'][0]['content']['parts'])
            ->firstWhere('inlineData.data')['inlineData']['data'] ?? null;

        if (!$base64) {
            throw new \Exception("No image returned by Gemini.");
        }

        $imageData = base64_decode($base64);

        if (!$filename) {
            $filename = "gemini-image-" . time() . ".png";
        }

        Storage::disk('s3')->put($filename, $imageData);

        return $filename;
    }
}
