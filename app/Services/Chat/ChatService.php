<?php

namespace App\Services\Chat;

use App\Services\AI\HackAIService;
use App\Services\AI\AIResponseParser;
use App\Services\AI\ModelResolver;
use App\Services\AI\TokenTracker;
use App\Models\Attachment;

class ChatService
{
    public function __construct(
        protected ChatHistoryService $history,
        protected HackAIService $ai,
        protected AIResponseParser $parser,
        protected ChatContextCache $cache,
        protected ModelResolver $resolver,
        protected TokenTracker $tracker
    ) {}

    public function handle(array $data, $user): array
    {
        $startTime = microtime(true);

        try {
            $session = $this->history->resolveSession(
                $data['session_id'] ?? null,
                $data['model'],
                $user->id
            );

            $this->history->storeUserMessage($session->id, $data['message'], $data['attachments'] ?? []);

            $context = $this->cache->get($session->id);
            $context[] = ['role' => 'user', 'content' => $data['message']];

            foreach ($data['attachments'] ?? [] as $attachmentId) {
                $text = $this->getAttachmentText($attachmentId, $user->id);
                if (!empty($text)) {
                    $context[] = [
                        'role' => 'user',
                        'content' => "File content: " . substr($text, 0, 1000)
                    ];
                }
            }

            $context = array_slice($context, -6);

            $model = $this->resolver->resolve($session->model, $data['message']);

            $maxTokens = $this->calculateMaxTokens($data['message'], $model);
            $raw = $this->ai->chat($context, $model, $maxTokens);

            $content = $this->parser->parse($raw);

            $this->history->storeAIMessage($session->id, $content);
            $this->cache->push($session->id, ['role' => 'assistant', 'content' => $content]);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'session_id' => $session->id,
                'content' => $content,
                'model_used' => $model,
                'processing_time_ms' => $processingTime,
                'success' => !empty($content)
            ];

        } catch (\Exception $e) {
            return [
                'session_id' => $data['session_id'] ?? null,
                'content' => 'Error: ' . $e->getMessage(),
                'model_used' => $data['model'] ?? 'unknown',
                'processing_time_ms' => 0,
                'success' => false
            ];
        }
    }

    private function calculateMaxTokens(string $message, string $model): int
    {
        $length = strlen($message);

        if (str_contains($model, 'mini')) {
            if ($length > 2000) return 300;
            if ($length > 1000) return 400;
            return 500;
        } else {
            if ($length > 2000) return 600;
            if ($length > 1000) return 800;
            return 1000;
        }
    }

    private function getAttachmentText($attachmentId, $userId): string
    {
        $attachment = Attachment::where('id', $attachmentId)
            ->where('user_id', $userId)
            ->first();

        return $attachment->text ?? '';
    }
}
