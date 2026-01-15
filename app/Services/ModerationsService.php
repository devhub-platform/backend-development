<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ModerationService
{
    public function moderateContent(string $text): bool
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' .env('HACKCLUB_API_KEY'),
        ])->post('https://ai.hackclub.com/proxy/v1/moderations', [
            'input' => $text
        ]);

        if($response->failed()) {
            return $response->json([
                'error' => 'Moderation request failed'
            ]);
        }


        return $response->json()['results'][0]['flagged'] ?? false;
    }
}
