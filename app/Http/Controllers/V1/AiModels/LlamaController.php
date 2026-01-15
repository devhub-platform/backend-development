<?php

namespace App\Http\Controllers\V1\AiModels;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LlamaController
{
    public $baseUrl = "https://llama-3-2-3b1.p.rapidapi.com/chat_completions";

    public function sendAiRequest(Request $request)
    {
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
