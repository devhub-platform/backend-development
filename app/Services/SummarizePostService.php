<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SummarizePostService
{
    protected $baseUrl = "https://article-extractor-and-summarizer.p.rapidapi.com/summarize-text";

    public function summarize(string $text, string $lang = 'en')
    {
        $response = Http::withHeaders([
            "x-rapidapi-key" => env('RAPIDAPI_KEY'),
            "x-rapidapi-host" => "article-extractor-and-summarizer.p.rapidapi.com",
            "Content-Type" => "application/json",
            "Accept" => "application/json",
            "Content-Length" => 1245,
            "Host" => "article-extractor-and-summarizer.p.rapidapi.com",

        ])->post($this->baseUrl, [
            "text" => $text,
            "lang" => $lang
        ]);

        if($response->failed()) {
            return null;
        }

        $data = $response->json();

        return $data['summary'] ?? null;
    }
}
