<?php

namespace App\Services\Chat;

use App\Services\AI\HackAIService;
use App\Services\AI\AIResponseParser;
use App\Services\AI\ModelResolver;
use App\Services\AI\TokenTracker;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class ChatService
{
    /** TTL in minutes for the presigned S3 URL sent to the AI vision API */
    private const VISION_URL_TTL_MINUTES = 60;

    public function __construct(
        protected ChatHistoryService $history,
        protected HackAIService      $ai,
        protected AIResponseParser   $parser,
        protected ChatContextCache   $cache,
        protected ModelResolver      $resolver,
        protected TokenTracker       $tracker
    ) {}

    public function handle(array $data, $user): array
    {
        set_time_limit(120);
        $startTime = microtime(true);

        try {
            // Resolve or create session
            $session = $this->history->resolveSession(
                $data['session_id'] ?? null,
                $data['model'],
                $user->id
            );

            $attachmentIds = $data['attachments'] ?? [];

            // Bug 1 Fix: Resolve the actual model FIRST before deciding on content format
            $model = $this->resolver->resolve($session->model, $data['message']);

            // If attachments present but resolved model lost vision, pin back to session model
            if (!empty($attachmentIds) && !$this->modelSupportsVision($model) && $this->modelSupportsVision($session->model)) {
                $model = $session->model;
            }

            $maxTokens = $this->calculateMaxTokens($data['message'], $model);

            // Build user message content based on resolved model capabilities
            $userMessage = $this->modelSupportsVision($model) && !empty($attachmentIds)
                ? $this->buildVisionContent($data['message'], $attachmentIds, $user->id)
                : $this->buildTextContent($data['message'], $attachmentIds, $user->id);

            // Persist raw message + attachment IDs to DB
            $this->history->storeUserMessage($session->id, $data['message'], $attachmentIds);

            // Build context from cache and append the new user message
            $context   = $this->cache->get($session->id);
            $context[] = ['role' => 'user', 'content' => $userMessage];

            // Send to API and parse response
            $raw     = $this->ai->chat($context, $model, $maxTokens);
            $content = $this->parser->parse($raw);

            // Persist AI response and sync cache with both turns
            $this->history->storeAIMessage($session->id, $content);
            $this->cache->push($session->id, ['role' => 'user',      'content' => is_array($userMessage) ? json_encode($userMessage) : $userMessage]);
            $this->cache->push($session->id, ['role' => 'assistant', 'content' => $content]);

            // Bug 4 Fix: success must be false when parser returns the fallback error string
            $isRealContent = !empty($content) && $content !== AIResponseParser::EMPTY_RESPONSE_MESSAGE;

            return [
                'session_id'         => $session->id,
                'content'            => $content,
                'model_used'         => $model,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'success'            => $isRealContent,
            ];

        } catch (\Exception $e) {
            return [
                'session_id'         => $data['session_id'] ?? null,
                'content'            => 'Error: ' . $e->getMessage(),
                'model_used'         => $data['model'] ?? 'unknown',
                'processing_time_ms' => 0,
                'success'            => false,
            ];
        }
    }

    /**
     * Build plain text content for non-vision models.
     * Appends extracted document text inline (max 1000 chars per attachment).
     */
    private function buildTextContent(string $message, array $attachmentIds, int $userId): string
    {
        if (empty($attachmentIds)) {
            return $message;
        }

        $parts = [$message];

        // Select only the columns needed - avoid fetching heavy fields
        $attachments = Attachment::whereIn('id', $attachmentIds)
            ->where('user_id', $userId)
            ->where('status', 'processed')
            ->whereNotNull('text')
            ->select(['id', 'text', 'filename'])
            ->get();

        foreach ($attachments as $attachment) {
            $parts[] = "\n[File: {$attachment->filename}]:\n" . substr($attachment->text, 0, 1000);
        }

        return implode("\n", $parts);
    }

    /**
     * Build vision-compatible content blocks for vision models.
     * Sends S3 URL directly — never base64 to avoid memory/timeout issues.
     */
    private function buildVisionContent(string $message, array $attachmentIds, int $userId): array
    {
        $content = [['type' => 'text', 'text' => $message]];

        // Select only url, s3_path and mime_type - minimal DB payload
        $attachments = Attachment::whereIn('id', $attachmentIds)
            ->where('user_id', $userId)
            ->select(['id', 'url', 's3_path', 'mime_type', 'type', 'text', 'filename', 'status'])
            ->get();

        foreach ($attachments as $attachment) {
            if ($attachment->type === 'image') {
                // Bug 3 Fix: Regenerate a fresh presigned URL at chat time (60-min TTL)
                $imageUrl = $this->resolveImageUrl($attachment);
                if ($imageUrl) {
                    $content[] = [
                        'type'      => 'image_url',
                        'image_url' => ['url' => $imageUrl],
                    ];
                }
            } elseif (!empty($attachment->text)) {
                // Document: append extracted text inline
                $content[] = [
                    'type' => 'text',
                    'text' => "\n[File: {$attachment->filename}]:\n" . substr($attachment->text, 0, 1000),
                ];
            }
        }

        return $content;
    }

    /**
     * Calculate max response tokens based on message length and model type.
     */
    private function calculateMaxTokens(string $message, string $model): int
    {
        $length = strlen($message);

        $isLightModel = str_contains($model, 'mini')
            || str_contains($model, 'lite')
            || str_contains($model, 'flash');

        if ($isLightModel) {
            if ($length > 2000) return 300;
            if ($length > 1000) return 400;
            return 500;
        }

        if ($length > 2000) return 600;
        if ($length > 1000) return 800;
        return 1000;
    }

    private function modelSupportsVision(string $modelId): bool
    {
        foreach (config('ai_models.chat', []) as $model) {
            if ($model['id'] === $modelId) {
                return !empty($model['vision']);
            }
            // Bug 2 Fix: The resolved model may be a fallback of a vision-capable parent
            if (!empty($model['vision']) && isset($model['fallback']) && $model['fallback'] === $modelId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Bug 3 Fix: Always regenerate a fresh presigned URL at chat time (60-min TTL).
     * Falls back to the stored URL if S3 temporary URLs are not supported.
     */
    private function resolveImageUrl(Attachment $attachment): ?string
    {
        if ($attachment->s3_path) {
            try {
                return Storage::disk('s3')->temporaryUrl($attachment->s3_path, now()->addMinutes(self::VISION_URL_TTL_MINUTES));
            } catch (\Exception) {
                // S3 driver does not support temporary URLs — fall through
            }
        }
        return $attachment->url ?: null;
    }
}
