<?php

namespace App\Services\Chat;

use App\Models\Post;
use App\Services\AI\HackAIService;
use App\Services\AI\AIResponseParser;

class PostChatService
{
    private const MODEL = 'google/gemini-2.5-flash'; // overridden by config

    public function __construct(
        protected HackAIService      $ai,
        protected AIResponseParser   $parser,
        protected ChatHistoryService $history,
        protected ChatContextCache   $cache,
        protected PostContextBuilder $contextBuilder,
    ) {}

    public function handle(Post $post, string $message, ?int $sessionId, int $userId): array
    {
        set_time_limit(120);
        $startTime = microtime(true);

        $model = config('ai_models.post_chat', self::MODEL);

        try {
            // Resolve or create session tied to this post
            $session = $this->history->resolveSession($sessionId, $model, $userId);

            // Build system prompt with post context
            $systemPrompt = $this->contextBuilder->build($post);

            // Get conversation history from cache
            $context = $this->cache->get($session->id);

            // If first message in session, inject system prompt
            if (empty($context)) {
                array_unshift($context, [
                    'role'    => 'system',
                    'content' => $systemPrompt,
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
}
