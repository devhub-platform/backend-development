<?php

namespace App\Services\Chat;

use App\Models\Attachment;
use App\Services\AI\AIResponseParser;
use App\Services\AI\HackAIService;
use App\Services\AI\ModelResolver;
use App\Services\AI\TokenTracker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ChatService
{
    public function __construct(
        protected ChatHistoryService $history,   // Persists and retrieves conversation history
        protected HackAIService      $ai,        // Communicates with the upstream AI API
        protected AIResponseParser   $parser,    // Normalises raw AI responses into clean text
        protected ChatContextCache   $cache,     // Short-lived Redis cache for conversation context
        protected ModelResolver      $resolver,  // Selects the optimal model based on message complexity
        protected TokenTracker       $tracker,   // Estimates token counts and request cost
        protected IngestionService   $ingestion, // Extracts plain text from uploaded documents
    ) {
    }

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Handle an incoming chat request end-to-end.
     *
     * Flow:
     *  1. Acquire a per-session distributed lock to prevent duplicate submissions.
     *  2. Resolve or create the chat session; reject on model mismatch.
     *  3. Build the user message payload (plain text or multimodal vision array).
     *  4. Persist the user turn and refresh the context window from cache.
     *  5. Call the AI with automatic retry on transient failures.
     *  6. Persist the assistant turn and push both turns into the context cache.
     *  7. Return a structured response payload to the controller.
     *
     * @param  array  $data  Validated request data: session_id, model, message, attachments
     * @param  mixed  $user  Authenticated user model
     * @return array         Response payload including session_id, content, model_used, timing
     */
    public function handle(array $data, $user): array
    {
        $startTime  = microtime(true);
        $sessionKey = 'chat:lock:' . ($data['session_id'] ?? 'new:' . $user->id);

        // Acquire a 15-second lock to prevent the same session from being
        // processed concurrently (e.g. double-tap from the frontend).
        $lock = Cache::lock($sessionKey, 15);

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

            // Reuse an existing session or create a new one.
            $session = $this->history->resolveSession(
                sessionId: $data['session_id'] ?? null,
                model:     $requestedModel ?? $defaultModel,
                userId:    $user->id
            );

            // A session is permanently bound to the model it was created with.
            // Switching models mid-session is not supported; the frontend should
            // open a new chat instead.
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

            // Vision-capable models receive a multimodal content array (text + image URLs).
            // All other models receive a plain string with any document text appended inline.
            $userMessage = ($modelSupportsVision && !empty($attachmentIds))
                ? $this->buildVisionContent($data['message'], $attachmentIds, $user->id)
                : $this->buildTextContent($data['message'], $attachmentIds, $user->id);

            // Persist the raw user message (without vision blocks) to the DB.
            $this->history->storeUserMessage($session->id, $data['message'], $attachmentIds);

            // Load the recent context window from cache and append the new user turn.
            $context   = $this->cache->get($session->id);
            $context[] = ['role' => 'user', 'content' => $userMessage];

            // Allow the resolver to downgrade to a cheaper fallback model for
            // simple messages (e.g. greetings, short factual questions).
            $model     = $this->resolver->resolve($session->model, $data['message']);
            $maxTokens = $this->calculateMaxTokens($data['message'], $model);

            // Call the AI; retries once on failure before re-throwing.
            $raw     = $this->callWithRetry($context, $model, $maxTokens);
            $content = $this->parser->parse($raw);

            // Persist the assistant reply to the DB.
            $this->history->storeAIMessage($session->id, $content);

            // Push both turns into the Redis context cache so the next message
            // has up-to-date conversation history without a DB round-trip.
            $this->cache->push($session->id, [
                'role'    => 'user',
                'content' => is_array($userMessage) ? json_encode($userMessage) : $userMessage,
            ]);

            $this->cache->push($session->id, [
                'role'    => 'assistant',
                'content' => $content,
            ]);

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
            // Always release the lock, even if an exception was thrown.
            $lock->release();
        }
    }

    // =========================================================================
    // AI Call
    // =========================================================================

    /**
     * Attempt the AI call up to $maxAttempts times.
     *
     * A 500 ms sleep is inserted between attempts to avoid hammering a
     * degraded upstream service. The last exception is re-thrown if all
     * attempts are exhausted.
     *
     * @throws \Exception
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
                    usleep(500000); // 500 ms
                }
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        // Final attempt if all previous iterations returned an empty result
        // without throwing (should not normally be reached).
        return $this->ai->chat($context, $model, $maxTokens);
    }

    // =========================================================================
    // Message Builders
    // =========================================================================

    /**
     * Build a plain-text user message.
     *
     * For models that do not support vision, document attachments are
     * appended as labelled text blocks (up to 1 000 chars each).
     * Images are silently skipped — they cannot be conveyed as text.
     */
    private function buildTextContent(string $message, array $attachmentIds, int $userId): string
    {
        if (empty($attachmentIds)) {
            return $message;
        }

        $parts = [$message];

        $attachments = Attachment::query()
            ->whereIn('id', $attachmentIds)
            ->where('user_id', $userId)
            ->get(['id', 'text', 'filename', 'status', 'type', 's3_path', 'extension']);

        foreach ($attachments as $attachment) {
            if ($attachment->status === 'processed' && $attachment->text) {
                // Truncate to 1 000 chars to stay within token budgets.
                $parts[] = "\n[File: " . $attachment->filename . "]:\n" . substr($attachment->text, 0, 1000);
            } elseif ($attachment->status === 'failed') {
                $parts[] = "\n[File: " . $attachment->filename . "]: Could not extract text from this file.";
            } elseif ($attachment->status === 'pending') {
                $parts[] = "\n[File: " . $attachment->filename . "]: File is still processing, please try again in a moment.";
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Build a multimodal content array for vision-capable models.
     *
     * Images are passed as public Azure Blob URLs — no presigned URLs are
     * required because the container is set to anonymous blob read access.
     * Documents are appended as inline text blocks, identical to buildTextContent().
     *
     * @return array<int, array{type: string, ...}>  OpenAI-compatible content array
     */
    private function buildVisionContent(string $message, array $attachmentIds, int $userId): array
    {
        // Always start with the user's text.
        $content = [['type' => 'text', 'text' => $message]];

        $attachments = Attachment::query()
            ->whereIn('id', $attachmentIds)
            ->where('user_id', $userId)
            ->get(['id', 'url', 'mime_type', 'type', 'text', 'filename', 'status', 's3_path', 'extension', 'size']);

        foreach ($attachments as $attachment) {
            if ($attachment->type === 'image') {
                $imageUrl = $this->resolveImageUrl($attachment);

                if (!$imageUrl) {
                    $content[] = ['type' => 'text', 'text' => "\n[Image: " . $attachment->filename . "]: Could not generate access URL."];
                    continue;
                }

                // Most vision APIs cap image size at 20 MB.
                if ($attachment->size && $attachment->size > 20 * 1024 * 1024) {
                    $content[] = ['type' => 'text', 'text' => "\n[Image: " . $attachment->filename . "]: Image too large to send to AI (max 20 MB)."];
                    continue;
                }

                $content[] = ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]];

            } else {
                // Non-image attachments are treated as documents regardless of model.
                if ($attachment->status === 'processed' && $attachment->text) {
                    $content[] = ['type' => 'text', 'text' => "\n[File: " . $attachment->filename . "]:\n" . substr($attachment->text, 0, 1000)];
                } elseif ($attachment->status === 'pending') {
                    $content[] = ['type' => 'text', 'text' => "\n[File: " . $attachment->filename . "]: Still processing, please try again."];
                } elseif ($attachment->status === 'failed') {
                    $content[] = ['type' => 'text', 'text' => "\n[File: " . $attachment->filename . "]: Could not extract text from this file."];
                }
            }
        }

        return $content;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Resolve the public URL for an image attachment.
     *
     * With Azure Blob Storage in public-container mode the `url` column already
     * holds a permanent public URL — no presigning is needed. We fall back to
     * reconstructing the URL via the Storage facade for legacy rows that predate
     * the Azure migration (rows that only have an s3_path).
     */
    private function resolveImageUrl(Attachment $attachment): ?string
    {
        // Preferred: use the URL persisted at upload time.
        if ($attachment->url) {
            return $attachment->url;
        }

        // Fallback: reconstruct from the stored blob/S3 path.
        $blobPath = $attachment->s3_path ?? null;

        if ($blobPath) {
            try {
                return Storage::disk('azure')->url($blobPath);
            } catch (\Exception $e) {
                // Storage driver misconfigured — surface null so the caller
                // can insert a graceful error message instead of crashing.
                return null;
            }
        }

        return null;
    }

    /**
     * Calculate the maximum number of output tokens for a given request.
     *
     * Lighter/faster models (mini, lite, flash) are given smaller ceilings to
     * keep their response times acceptable. Heavier models are given more room
     * for detailed answers.
     */
    private function calculateMaxTokens(string $message, string $model): int
    {
        $length       = strlen($message);
        $isLightModel = str_contains($model, 'mini')
            || str_contains($model, 'lite')
            || str_contains($model, 'flash');

        if ($isLightModel) {
            if ($length > 2000) return 1000;
            if ($length > 1000) return 1500;
            return 2000;
        }

        if ($length > 2000) return 2000;
        if ($length > 1000) return 3000;
        return 4000;
    }

    /**
     * Check whether the given model ID supports image (vision) input.
     *
     * Vision support is declared in config/ai_models.php via a `vision: true`
     * flag on each model entry.
     */
    private function modelSupportsVision(string $modelId): bool
    {
        $models = config('ai_models.chat', []);

        foreach ($models as $model) {
            if ($model['id'] === $modelId) {
                return !empty($model['vision']);
            }
        }

        return false;
    }
}
