<?php

namespace App\Services\Chat;

use App\Models\Question;
use App\Services\AI\HackAIService;
use App\Services\AI\AIResponseParser;

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

    public function handle(Question $question, string $message, ?int $sessionId, int $userId): array
    {
        set_time_limit(120);
        $startTime = microtime(true);

        $model = config('ai_models.question_chat', self::MODEL);

        try {
            // Eager load all relations in one query - avoids N+1
            $question->load([
                'answers',
                'answers.votes',
            ]);

            // Resolve or create session
            $session = $this->history->resolveSession($sessionId, $model, $userId);

            // Build context from cache
            $context = $this->cache->get($session->id);

            // Inject system prompt on first message only
            if (empty($context)) {
                array_unshift($context, [
                    'role'    => 'system',
                    'content' => $this->contextBuilder->build($question),
                ]);
            }

            // Append current user message
            $context[] = ['role' => 'user', 'content' => $message];

            // Send to AI
            $raw     = $this->ai->chat($context, $model, 800);
            $content = $this->parser->parse($raw);

            // Persist to DB and sync cache
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
}
