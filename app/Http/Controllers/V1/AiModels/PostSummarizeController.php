<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Models\Post;
use App\Services\SummarizeLlamaService;
use App\Services\SummarizePostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostSummarizeController
{
    public function __construct(
        private SummarizePostService  $summarizeService,
        private SummarizeLlamaService $llamaService
    )
    {
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

    public function summarizePostUsingLlama(Request $request, Post $post): JsonResponse
    {
        return $this->llamaService->summarize($post->content, 'concise');
    }


    public function translatePost(Request $request, Post $post): JsonResponse
    {
        $language = $request->query('language', 'English');

        return $this->llamaService->translate($post->content, $language);
    }


    public function analyzePost(Request $request, Post $post): JsonResponse
    {
        $analysisType = $request->query('type', 'sentiment');

        return $this->llamaService->analyze($post->content, $analysisType);
    }

    public function answerQuestionAboutPost(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|max:1000',
        ]);

        $question = $request->input('question');

        return $this->llamaService->answerQuestion($post->content, $question);
    }

    public function generateContent(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string|max:500',
            'type' => 'string|in:article,blog,poem,story,script|default=article',
        ]);

        $topic = $request->input('topic');
        $contentType = $request->input('type', 'article');

        return $this->llamaService->generate($topic, $contentType);
    }
}
