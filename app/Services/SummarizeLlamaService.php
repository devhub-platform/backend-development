<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SummarizeLlamaService
{
    private const OPERATIONS = [
        'summarize' => 'Please summarize the following content in a concise manner',
        'translate' => 'Please translate the following content to',
        'analyze' => 'Please analyze the following content and provide insights',
        'generate' => 'Please generate content based on',
        'qa' => 'Answer the following question based on the content',
        'general' => 'Please help with the following'
    ];

    /**
     * Send a general AI request to Llama 3
     */
    public function sendAiRequest(Request $request)
    {
        $request->validate([
            'prompt' => 'string|max:5000|min:3',
        ]);

        $prompt = $request->input('prompt');

        return $this->callLlamaAPI($prompt);
    }

    /**
     * Summarize content
     */
    public function summarize(string $content, string $style = 'concise'): \Illuminate\Http\JsonResponse
    {
        $prompt = "Please summarize the following content in a {$style} manner:\n\n{$content}";
        return $this->callLlamaAPI($prompt);
    }

    /**
     * Translate content
     */
    public function translate(string $content, string $targetLanguage = 'English'): \Illuminate\Http\JsonResponse
    {
        $prompt = "Please translate the following content to {$targetLanguage}:\n\n{$content}";
        return $this->callLlamaAPI($prompt);
    }

    /**
     * Analyze content
     */
    public function analyze(string $content, string $analysisType = 'general'): \Illuminate\Http\JsonResponse
    {
        $prompt = "Please analyze the following content for {$analysisType} insights:\n\n{$content}";
        return $this->callLlamaAPI($prompt);
    }

    /**
     * Answer questions about content
     */
    public function answerQuestion(string $content, string $question): \Illuminate\Http\JsonResponse
    {
        $prompt = "Based on the following content:\n\n{$content}\n\nPlease answer this question: {$question}";
        return $this->callLlamaAPI($prompt);
    }

    /**
     * Generate content based on a topic
     */
    public function generate(string $topic, string $contentType = 'article'): \Illuminate\Http\JsonResponse
    {
        $prompt = "Please generate a {$contentType} about: {$topic}";
        return $this->callLlamaAPI($prompt);
    }

    /**
     * General conversation with the model
     */
    public function chat(string $message): \Illuminate\Http\JsonResponse
    {
        return $this->callLlamaAPI($message);
    }

    /**
     * Core method to call Llama API
     */
    private function callLlamaAPI(string $prompt): \Illuminate\Http\JsonResponse
    {
        try {
            $base_url = config('services.llama.base_url');

            $response = Http::timeout(60)->withHeaders([
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
                Log::error('Llama API request failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

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
            ], 200);

        } catch (\Exception $e) {
            Log::error('Llama API Exception', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return response()->json([
                'error' => 'An error occurred while processing your request',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
