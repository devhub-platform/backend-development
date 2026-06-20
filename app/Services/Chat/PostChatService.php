<?php

namespace App\Services\Chat;

use App\Models\Post;
use App\Services\AI\AIResponseParser;
use App\Services\AI\HackAIService;

class PostChatService
{
    private const MODEL = 'google/gemini-2.5-flash';

    public function __construct(
        protected HackAIService      $ai,
        protected AIResponseParser   $parser,
        protected ChatHistoryService $history,
        protected ChatContextCache   $cache,
        protected PostContextBuilder $contextBuilder,
    ) {}

    /**
     * Handle a chat message in the context of a specific post.
     *
     * The post content is injected as a system prompt on the first turn,
     * then cached for subsequent turns in the same session.
     */
    public function handle(Post $post, string $message, ?int $sessionId, int $userId): array
    {
        $startTime = microtime(true);
        $model     = config('ai_models.post_chat', self::MODEL);

        try {
            $session = $this->history->resolveSession($sessionId, $model, $userId);
            $context = $this->cache->get($session->id);

            if (empty($context)) {
                $context[] = [
                    'role'    => 'system',
                    'content' => $this->contextBuilder->build($post),
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
                'post_id'            => $post->id,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'success'            => !empty($content),
            ];

        } catch (\Exception $e) {
            return [
                'session_id'         => $sessionId,
                'content'            => 'Error: ' . $e->getMessage(),
                'model_used'         => $model,
                'post_id'            => $post->id,
                'processing_time_ms' => 0,
                'success'            => false,
            ];
        }
    }

    /**
     * Attempt the AI call up to $maxAttempts times with a 500ms delay between retries.
     *
     * @throws \Exception Re-throws the last exception if all attempts fail.
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
            } catch (\Exception $e) {
                $lastException = $e;
                if ($attempt < $maxAttempts) {
                    usleep(500_000);
                }
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        return $this->ai->chat($context, $model, $maxTokens);
    }
}
