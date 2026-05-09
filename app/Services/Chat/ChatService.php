<?php

namespace App\Services\Chat;

use App\Models\Attachment;
use App\Services\AI\AIResponseParser;
use App\Services\AI\HackAIService;
use App\Services\AI\ModelResolver;
use App\Services\AI\TokenTracker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatService
{
    /**
     * How each vision-capable model wants to receive images.
     *
     * 'both'   → try base64 first, fall back to URL if it fails
     * 'base64' → inline base64 data URI only
     * 'url'    → public URL only (model fetches it directly)
     *
     * Add or change entries here when a model's behaviour is confirmed.
     * Models not listed default to 'both'.
     */
    private const IMAGE_STRATEGY = [
        'google/gemini-2.5-flash' => 'both',
        'x-ai/grok-4.1-fast'     => 'both',
        'openai/gpt-oss-120b'     => 'both',
        'qwen/qwen3-235b-a22b'    => 'url',    // qwen tends to prefer URLs
        'moonshotai/kimi-k2-thinking'          => 'url',
        'deepseek/deepseek-v3.2-speciale'      => 'url',
        'deepseek/deepseek-v3.2'               => 'url',
    ];

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
     */
    public function handle(array $data, $user): array
    {
        $startTime  = microtime(true);
        $sessionKey = 'chat:lock:' . ($data['session_id'] ?? 'new:' . $user->id);

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

            $userMessage = ($modelSupportsVision && !empty($attachmentIds))
                ? $this->buildVisionContent($data['message'], $attachmentIds, $user->id, $session->model)
                : $this->buildTextContent($data['message'], $attachmentIds, $user->id);

            $this->history->storeUserMessage($session->id, $data['message'], $attachmentIds);

            $context   = $this->cache->get($session->id);
            $context[] = ['role' => 'user', 'content' => $userMessage];

            $model     = $this->resolver->resolve($session->model, $data['message']);
            $maxTokens = $this->calculateMaxTokens($data['message'], $model);

            $raw     = $this->callWithRetry($context, $model, $maxTokens);
            $content = $this->parser->parse($raw);

            $this->history->storeAIMessage($session->id, $content);

            // Store only plain text in cache — never base64 blobs.
            $this->cache->push($session->id, [
                'role'    => 'user',
                'content' => $data['message'],
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
            $lock->release();
        }
    }

    // =========================================================================
    // AI Call
    // =========================================================================

    /**
     * Attempt the AI call up to $maxAttempts times with a 500 ms pause between
     * attempts. Re-throws the last exception if all attempts are exhausted.
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
                    usleep(500000);
                }
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        return $this->ai->chat($context, $model, $maxTokens);
    }

    // =========================================================================
    // Message Builders
    // =========================================================================

    /**
     * Build a plain-text user message.
     * Documents are appended as labelled text blocks (truncated to 4 000 chars).
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
            ->get(['id', 'text', 'filename', 'status', 'type', 'blob_path', 'extension']);

        foreach ($attachments as $attachment) {
            if ($attachment->status === 'processed' && $attachment->text) {
                $parts[] = "\n[File: " . $attachment->filename . "]:\n" . substr($attachment->text, 0, 4000);
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
     * The image delivery strategy is determined per-model via IMAGE_STRATEGY:
     *   'base64' → inline base64 data URI (server fetches the image)
     *   'url'    → public Azure URL (the AI proxy fetches the image)
     *   'both'   → try base64 first; fall back to URL if bytes unavailable
     *
     * Documents are always appended as plain text blocks.
     */
    private function buildVisionContent(string $message, array $attachmentIds, int $userId, string $modelId): array
    {
        $content  = [['type' => 'text', 'text' => $message]];
        $strategy = self::IMAGE_STRATEGY[$modelId] ?? 'both';

        $attachments = Attachment::query()
            ->whereIn('id', $attachmentIds)
            ->where('user_id', $userId)
            ->get(['id', 'url', 'mime_type', 'type', 'text', 'filename', 'status', 'blob_path', 'extension', 'size']);

        foreach ($attachments as $attachment) {
            if ($attachment->type === 'image') {
                $this->appendImageBlock($content, $attachment, $strategy);
            } else {
                $this->appendDocumentBlock($content, $attachment);
            }
        }

        return $content;
    }

    // =========================================================================
    // Image Handling
    // =========================================================================

    /**
     * Append an image block using the strategy appropriate for the model.
     *
     * Strategy 'base64':
     *   Fetch bytes server-side → send as inline data URI.
     *   Pro: model never needs to reach the internet.
     *   Con: larger request payload.
     *
     * Strategy 'url':
     *   Send the public Azure blob URL directly.
     *   Pro: lightweight request.
     *   Con: requires the AI proxy to be able to fetch the URL.
     *
     * Strategy 'both':
     *   Try base64 first (most reliable).
     *   Fall back to URL if bytes cannot be fetched (e.g. stream error).
     *   Fall back to a text notice if the URL is also unavailable.
     *
     * @param  array      &$content
     * @param  Attachment  $attachment
     * @param  string      $strategy   'base64' | 'url' | 'both'
     */
    private function appendImageBlock(array &$content, Attachment $attachment, string $strategy): void
    {
        // Hard limit: most vision APIs reject images over 20 MB.
        if ($attachment->size && $attachment->size > 20 * 1024 * 1024) {
            $content[] = [
                'type' => 'text',
                'text' => "\n[Image: " . $attachment->filename . "]: Image too large to send to AI (max 20 MB).",
            ];
            return;
        }

        $mimeType  = $attachment->mime_type ?: 'image/jpeg';
        $blobPath  = $attachment->blob_path ?? null;
        $publicUrl = $attachment->url ?? null;

        // ── Strategy: url ─────────────────────────────────────────────────────
        if ($strategy === 'url') {
            if ($publicUrl) {
                $content[] = $this->buildUrlBlock($publicUrl);
                return;
            }

            // URL not available — fall through to text notice.
            $this->appendImageError($content, $attachment, 'No public URL available.');
            return;
        }

        // ── Strategy: base64 ──────────────────────────────────────────────────
        if ($strategy === 'base64') {
            $bytes = $this->fetchBytesFromStream($blobPath)
                ?? $this->fetchBytesFromUrl($publicUrl);

            if ($bytes) {
                $content[] = $this->buildBase64Block($bytes, $mimeType);
                return;
            }

            $this->appendImageError($content, $attachment, 'Could not fetch image bytes.');
            return;
        }

        // ── Strategy: both (default) ──────────────────────────────────────────
        // 1. Try inline base64 via Azure stream (fastest, no proxy dependency).
        $bytes = $this->fetchBytesFromStream($blobPath);
        if ($bytes) {
            $content[] = $this->buildBase64Block($bytes, $mimeType);
            return;
        }

        // 2. Try inline base64 via HTTP GET on the public URL.
        $bytes = $this->fetchBytesFromUrl($publicUrl);
        if ($bytes) {
            $content[] = $this->buildBase64Block($bytes, $mimeType);
            return;
        }

        // 3. Fall back to sending the URL directly (proxy fetches it).
        if ($publicUrl) {
            Log::warning('ChatService: base64 fetch failed, falling back to URL', [
                'attachment_id' => $attachment->id,
            ]);
            $content[] = $this->buildUrlBlock($publicUrl);
            return;
        }

        // 4. Everything failed — insert a graceful text notice.
        $this->appendImageError($content, $attachment, 'All delivery methods failed.');
    }

    /**
     * Append a document text block (status-aware).
     */
    private function appendDocumentBlock(array &$content, Attachment $attachment): void
    {
        if ($attachment->status === 'processed' && $attachment->text) {
            $content[] = [
                'type' => 'text',
                'text' => "\n[File: " . $attachment->filename . "]:\n" . substr($attachment->text, 0, 4000),
            ];
        } elseif ($attachment->status === 'pending') {
            $content[] = [
                'type' => 'text',
                'text' => "\n[File: " . $attachment->filename . "]: Still processing, please try again.",
            ];
        } elseif ($attachment->status === 'failed') {
            $content[] = [
                'type' => 'text',
                'text' => "\n[File: " . $attachment->filename . "]: Could not extract text from this file.",
            ];
        }
    }

    // =========================================================================
    // Image Fetch & Build Helpers
    // =========================================================================

    /**
     * Fetch raw image bytes via the Azure Storage SDK stream.
     * No HTTP round-trip — reads directly from the storage account.
     *
     * @return string|null  Raw bytes, or null on any failure.
     */
    private function fetchBytesFromStream(?string $blobPath): ?string
    {
        if (!$blobPath) {
            return null;
        }

        try {
            $stream = Storage::disk('azure')->readStream($blobPath);
            if (!$stream) {
                return null;
            }

            $bytes = stream_get_contents($stream);
            fclose($stream);

            return $bytes ?: null;

        } catch (\Throwable $e) {
            Log::debug('ChatService: Azure stream fetch failed', [
                'blob_path' => $blobPath,
                'error'     => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fetch raw image bytes via an HTTP GET request to the public URL.
     * Used as a fallback when the Azure SDK stream is unavailable.
     *
     * @return string|null  Raw bytes, or null on any failure.
     */
    private function fetchBytesFromUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        try {
            $response = Http::timeout(15)->get($url);

            if ($response->successful()) {
                return $response->body() ?: null;
            }

            Log::debug('ChatService: HTTP image fetch returned non-200', [
                'url'    => $url,
                'status' => $response->status(),
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::debug('ChatService: HTTP image fetch failed', [
                'url'   => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build an inline base64 image block (OpenAI image_url / data-URI format).
     * Universally supported across OpenAI-compatible proxies.
     */
    private function buildBase64Block(string $bytes, string $mimeType): array
    {
        return [
            'type'      => 'image_url',
            'image_url' => [
                'url'    => 'data:' . $mimeType . ';base64,' . base64_encode($bytes),
                'detail' => 'auto',
            ],
        ];
    }

    /**
     * Build a URL-reference image block.
     * The AI proxy fetches the image itself using the provided public URL.
     */
    private function buildUrlBlock(string $url): array
    {
        return [
            'type'      => 'image_url',
            'image_url' => [
                'url'    => $url,
                'detail' => 'auto',
            ],
        ];
    }

    /**
     * Append a graceful text error message when an image cannot be delivered.
     * Logs the reason for debugging.
     */
    private function appendImageError(array &$content, Attachment $attachment, string $reason): void
    {
        Log::error('ChatService: image delivery failed', [
            'attachment_id' => $attachment->id,
            'reason'        => $reason,
        ]);

        $content[] = [
            'type' => 'text',
            'text' => "\n[Image: " . $attachment->filename . "]: Could not load image for analysis. Please re-upload and try again.",
        ];
    }

    // =========================================================================
    // Misc Helpers
    // =========================================================================

    /**
     * Calculate the max output tokens based on message length and model tier.
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
     * Vision support is declared in config/ai_models.php via a `vision: true` flag.
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
