<?php

namespace App\Services\Chat;

use App\Models\Question;
use App\Services\AI\AIResponseParser;
use App\Services\AI\HackAIService;

class QuestionChatService
{
    private const MODEL = 'google/gemini-2.5-flash';

    public function __construct(
        protected HackAIService          $ai,
        protected AIResponseParser       $parser,
        protected ChatHistoryService     $history,
        protected ChatContextCache       $cache,
        protected QuestionContextBuilder $contextBuilder,
    ) {}

    /**
     * Handle a chat message in the context of a specific Q&A question.
     *
     * Answers and votes are eager-loaded once to avoid N+1 queries.
     * The question context is injected as a system prompt on the first turn only.
     */
    public function handle(Question $question, string $message, ?int $sessionId, int $userId): array
    {
        $startTime = microtime(true);
        $model     = config('ai_models.question_chat', self::MODEL);

        try {
            $question->loadMissing(['answers', 'answers.votes']);

            $session = $this->history->resolveSession($sessionId, $model, $userId);
            $context = $this->cache->get($session->id);

            if (empty($context)) {
                $context[] = [
                    'role'    => 'system',
                    'content' => $this->contextBuilder->build($question),
                ];
            }

            $context[] = ['role' => 'user', 'content' => $message];

            $raw     = $this->callWithRetry($context, $model, 800);
            $content = $this->parser->parse($raw);

            $this->history->storeUserMessage($session->id, $message, []);
            $this->history->storeAIMessage($session->id, $content);
            $this->cache->push($session->id, ['role' => 'user',      'content' => $message]);
            $this->cache->push($session->id, ['role' => 'assistant', 'content' => $content]);

            return [
                'session_id'         => $session->id,
                'content'            => $content,
                'model_used'         => $model,
                'question_id'        => $question->id,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'success'            => true,
            ];

        } catch (\Exception $e) {
            return [
                'session_id'         => $sessionId,
                'content'            => 'Error: ' . $e->getMessage(),
                'model_used'         => $model,
                'question_id'        => $question->id,
                'processing_time_ms' => 0,
                'success'            => false,
            ];
        }
    }

    /**
     * Attempt the AI call up to $maxAttempts times with a 500ms delay between retries.
     *
     * FIX: Previous code had a dangerous fallback — if all attempts returned empty but
     * threw no exception, it would call the AI a final time outside the loop silently.
     * Now we throw explicitly if all attempts fail or return empty.
     *
     * @throws \RuntimeException if all attempts fail or return empty
     */
    private function callWithRetry(array $context, string $model, int $maxTokens, int $maxAttempts = 2): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $result = $this->ai->chat($context, $model, $maxTokens);

                if (!empty($result)) {
                    return $result;
                }

                // Got an empty response — treat like a failure and retry
                $lastException = new \RuntimeException(
                    "AI service returned an empty response on attempt {$attempt}/{$maxAttempts}"
                );

            } catch (\Exception $e) {
                $lastException = $e;
            }

            if ($attempt < $maxAttempts) {
                usleep(500_000); // 500ms between retries
            }
        }

        // All attempts exhausted — throw the last known exception
        throw $lastException ?? new \RuntimeException(
            'AI service failed after ' . $maxAttempts . ' attempts with no exception details'
        );
    }
}
