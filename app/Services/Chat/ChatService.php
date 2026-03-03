<?php

namespace App\Services\Chat;

use App\Models\Attachment;
use App\Services\AI\AIResponseParser;
use App\Services\AI\HackAIService;
use App\Services\AI\ModelResolver;
use App\Services\AI\TokenTracker;
use Illuminate\Support\Facades\Cache;

class ChatService
{
    public function __construct(
        protected ChatHistoryService $history,
        protected HackAIService      $ai,
        protected AIResponseParser   $parser,
        protected ChatContextCache   $cache,
        protected ModelResolver      $resolver,
        protected TokenTracker       $tracker,
        protected IngestionService   $ingestion,
    ) {}

    /**
     * Handle an incoming chat request.
     *
     * Resolves the session, builds message content, calls the AI,
     * persists both turns, and returns the response payload.
     */
    public function handle(array $data, $user): array
    {
        $startTime  = microtime(true);
        $sessionKey = 'chat:lock:' . ($data['session_id'] ?? 'new:' . $user->id);
        $lock       = Cache::lock($sessionKey, 15);

        if (!$lock->get()) {
            return [
                'session_id'         => $data['session_id'] ?? null,
                'content'            => null,
                'model_used'         => $data['model'] ?? config('ai_models.default'),
                'processing_time_ms' => 0,
                'success'            => false,
                'error'              => 'already_processing',
                'message'            => 'Your previous message is still being processed. Please wait.',
            ];
        }

        try {
            $requestedModel = $data['model'] ?? null;
            $defaultModel   = config('ai_models.default');

            $session = $this->history->resolveSession(
                sessionId: $data['session_id'] ?? null,
                model:     $requestedModel ?? $defaultModel,
                userId:    $user->id
            );

            if ($requestedModel && $requestedModel !== $session->model) {
                return [
                    'session_id'         => null,
                    'content'            => null,
                    'model_used'         => $session->model,
                    'processing_time_ms' => 0,
                    'success'            => false,
                    'error'              => 'model_mismatch',
                    'message'            => 'This session is bound to ' . $session->model . '. Please start a new chat to use a different model.',
                    'hint'               => 'Create a new session with the desired model.',
                ];
            }

            $attachmentIds       = $data['attachments'] ?? [];
            $modelSupportsVision = $this->modelSupportsVision($session->model);

            $userMessage = $modelSupportsVision && !empty($attachmentIds)
                ? $this->buildVisionContent($data['message'], $attachmentIds, $user->id)
                : $this->buildTextContent($data['message'], $attachmentIds, $user->id);

            $this->history->storeUserMessage($session->id, $data['message'], $attachmentIds);

            $context   = $this->cache->get($session->id);
            $context[] = ['role' => 'user', 'content' => $userMessage];

            $model     = $this->resolver->resolve($session->model, $data['message']);
            $maxTokens = $this->calculateMaxTokens($data['message'], $model);

            $raw     = $this->callWithRetry($context, $model, $maxTokens);
            $content = $this->parser->parse($raw);

            $this->history->storeAIMessage($session->id, $content);
            $this->cache->push($session->id, ['role' => 'user',      'content' => is_array($userMessage) ? json_encode($userMessage) : $userMessage]);
            $this->cache->push($session->id, ['role' => 'assistant', 'content' => $content]);

            return [
                'session_id'         => $session->id,
                'content'            => $content,
                'model_used'         => $model,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'success'            => !empty($content),
            ];

        } catch (\Exception $e) {
            return [
                'session_id'         => $data['session_id'] ?? null,
                'content'            => 'Error: ' . $e->getMessage(),
                'model_used'         => $data['model'] ?? config('ai_models.default'),
                'processing_time_ms' => 0,
                'success'            => false,
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * Attempt the AI call up to $maxAttempts times.
     * Waits 500ms between attempts to avoid hammering a degraded service.
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

    /**
     * Build a plain-text message, appending extracted document content where available.
     * Used for models that do not support vision.
     */
    private function buildTextContent(string $message, array $attachmentIds, int $userId): string
    {
        if (empty($attachmentIds)) {
            return $message;
        }

        $parts       = [$message];
        $attachments = Attachment::whereIn('id', $attachmentIds)
            ->where('user_id', $userId)
            ->get(['id', 'text', 'filename', 'status', 'type', 's3_path', 'extension']);

        foreach ($attachments as $attachment) {
            if ($attachment->status === 'processed' && $attachment->text) {
                $parts[] = "\n[File: {$attachment->filename}]:\n" . substr($attachment->text, 0, 1000);
            } elseif ($attachment->status === 'failed') {
                $parts[] = "\n[File: {$attachment->filename}]: Could not extract text from this file.";
            } elseif ($attachment->status === 'pending') {
                $parts[] = "\n[File: {$attachment->filename}]: File is still processing, please try again in a moment.";
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Build a multimodal content array for vision-capable models.
     * Images are sent as presigned URLs; documents are appended as text blocks.
     */
    private function buildVisionContent(string $message, array $attachmentIds, int $userId): array
    {
        $content     = [['type' => 'text', 'text' => $message]];
        $attachments = Attachment::whereIn('id', $attachmentIds)
            ->where('user_id', $userId)
            ->get(['id', 'url', 'mime_type', 'type', 'text', 'filename', 'status', 's3_path', 'extension', 'size']);

        foreach ($attachments as $attachment) {
            if ($attachment->type === 'image') {
                $imageUrl = $this->resolveImageUrl($attachment);

                if (!$imageUrl) {
                    $content[] = ['type' => 'text', 'text' => "\n[Image: {$attachment->filename}]: Could not generate access URL."];
                    continue;
                }

                if ($attachment->size && $attachment->size > 20 * 1024 * 1024) {
                    $content[] = ['type' => 'text', 'text' => "\n[Image: {$attachment->filename}]: Image too large to send to AI (max 20MB)."];
                    continue;
                }

                $content[] = ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]];
            } else {
                if ($attachment->status === 'processed' && $attachment->text) {
                    $content[] = ['type' => 'text', 'text' => "\n[File: {$attachment->filename}]:\n" . substr($attachment->text, 0, 1000)];
                } elseif ($attachment->status === 'pending') {
                    $content[] = ['type' => 'text', 'text' => "\n[File: {$attachment->filename}]: Still processing, please try again."];
                } elseif ($attachment->status === 'failed') {
                    $content[] = ['type' => 'text', 'text' => "\n[File: {$attachment->filename}]: Could not extract text from this file."];
                }
            }
        }

        return $content;
    }

    /**
     * Return a short-lived presigned URL for the given attachment.
     * Falls back to the stored URL if the S3 driver does not support presigning.
     */
    private function resolveImageUrl(Attachment $attachment): ?string
    {
        if ($attachment->s3_path) {
            try {
                return \Illuminate\Support\Facades\Storage::disk('s3')
                    ->temporaryUrl($attachment->s3_path, now()->addMinutes(10));
            } catch (\Exception) {
                // S3 driver does not support temporary URLs — fall through
            }
        }

        return $attachment->url ?: null;
    }

    /**
     * Determine max output tokens based on message length and model tier.
     * Lighter models get lower ceilings to keep response times acceptable.
     */
    private function calculateMaxTokens(string $message, string $model): int
    {
        $length       = strlen($message);
        $isLightModel = str_contains($model, 'mini')
            || str_contains($model, 'lite')
            || str_contains($model, 'flash');

        if ($isLightModel) {
            return match(true) {
                $length > 2000 => 1000,
                $length > 1000 => 1500,
                default        => 2000,
            };
        }

        return match(true) {
            $length > 2000 => 2000,
            $length > 1000 => 3000,
            default        => 4000,
        };
    }

    /**
     * Check whether the given model supports image input.
     */
    private function modelSupportsVision(string $modelId): bool
    {
        foreach (config('ai_models.chat', []) as $model) {
            if ($model['id'] === $modelId) {
                return !empty($model['vision']);
            }
        }

        return false;
    }
}
