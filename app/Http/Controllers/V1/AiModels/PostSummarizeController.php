<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Models\Post;
use App\Services\SummarizePostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostSummarizeController
{
    private SummarizePostService $summarizeService;

    public function __construct(SummarizePostService $summarizeService)
    {
        $this->summarizeService = $summarizeService;
    }

    public function summarizePost(Request $request, Post $post, string $lang = 'en'): JsonResponse
    {
        $lang = $request->query('lang', $lang);

        $result = $this->summarizeService->summarize(
            text: $post->content,
            lang: $lang
        );

        if (isset($result['error'])) {
            return response()->json([
                'message' => 'Summarization failed',
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'message' => $result['cached'] ? 'Retrieved from cache' : 'Generated successfully',
            'summary' => $result['summary'],
            'cached' => $result['cached'],
        ], 200);
    }

    public function getSupportedLanguages(): JsonResponse
    {
        $languages = SummarizePostService::getSupportedLanguages();

        return response()->json([
            'message' => 'Supported languages',
            'count' => count($languages),
            'languages' => $languages,
        ], 200);
    }
}
