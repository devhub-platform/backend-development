<?php

namespace App\Http\Controllers\V1\AiModels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LlamaController
{
    public function sendAiRequest(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000|min:3',
        ]);

        $prompt = $request->input('prompt') ??
            $request->input('q');

        $base_url = config('services.Llama.base_url');

        $response = Http::withHeaders([
            "x-rapidapi-key" => config('services.Llama.api_key'),
            "x-rapidapi-host" => config('services.Llama.host'),
            "Content-Type" => "application/json",
        ])->post($base_url, [
            "messages" => [
                [
                    "role" => "user",
                    "content" => $prompt
                ]
            ]
        ]);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Llama 3 API request failed'
            ], 500);
        }

        $data = $response->json();

        $content = $data['choices'][0]['message']['content'] ?? 'No response';

        $user = auth()->user()->name;
        return response()->json([
            "Hi $user! " => 'This is Llama 3 Model Response',
            'content' => $content
        ]);
    }
}
