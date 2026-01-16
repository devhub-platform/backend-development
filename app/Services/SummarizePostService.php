<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SummarizePostService
{
    protected $baseUrl = "https://article-extractor-and-summarizer.p.rapidapi.com/summarize-text";

    public function summarize(string $text, string $lang = 'en')
    {
        $response = Http::withHeaders([
            "x-rapidapi-key" => config('services.summarize.api_key'),
            "x-rapidapi-host" => config('services.summarize.host'),
            "Content-Type" => "application/json",
            "Accept" => "application/json",
            "Content-Length" => 1500,
            "Host" => config('services.summarize.host'),

        ])->post($this->baseUrl, [
            "text" => $text,
            "lang" => $lang
        ]);

        if ($response->failed()) {
            return $response->json([
                'error' => 'Summarization request failed'
            ]);
        }

        $data = $response->json();

        return $data['summary'] ?? null;
    }
}
