<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LlamaModelService
{
    public function sendAiRequest(Request $request)
    {
        $request->validate([
            'prompt' => 'string|max:1000|min:3',
        ]);

        $prompt = $request->input('prompt');

        $base_url = config('services.llama.base_url');

        $response = Http::withHeaders([
            'x-rapidapi-key' => config('services.llama.api_key'),
            'x-rapidapi-host' => config('services.llama.host'),
            'Accept' => 'application/json',
        ])->post($base_url, [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ]
            ]
        ]);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Llama 3 API request failed',
                'details' => $response->json(),
            ], $response->status());
        }

        $content = data_get($response->json(), 'choices.0.message.content', 'No response');

        $user = optional(auth()->user())->name ?? 'Guest';

        return response()->json([
            'message' => "Hi $user! This is Llama 3 Model Response",
            'content' => $content,
        ]);
    }
}
