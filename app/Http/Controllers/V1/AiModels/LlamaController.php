<?php

namespace App\Http\Controllers\V1\AiModels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LlamaController
{
    public $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('LLAMA_API_URL');
    }

    public function sendRequest(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:2000',
        ]);

        $prompt = $request->input('prompt');

        $response = Http::withHeaders([
            "x-rapidapi-key" => env('LLAMA_RAPIDAPI_KEY'),
            "x-rapidapi-host" => "llama-3-2-3b1.p.rapidapi.com",
            "Content-Type" => "application/json",
        ])->post($this->baseUrl, [
            "messages" => [
                [
                    "role" => "user",
                    "content" => $prompt
                ]
            ]
        ]);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Failed to get response from Llama 3 API',
                'details' => $response->body()
            ], 500);
        }

        $data = $response->json();

        $content = $data['choices'][0]['message']['content'] ?? 'No response';

        $user = auth()->user()->name;
        return response()->json([
            'message' => "Hi $user! This is Llama 3 Model Response:",
            'content' => $content
        ]);
    }
}
