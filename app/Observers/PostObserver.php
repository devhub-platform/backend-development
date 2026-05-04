<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\AI\EmbeddingService;
use App\Services\AI\AddPostToAI;
use Illuminate\Support\Facades\Log;

class PostObserver
{
    public function __construct(private EmbeddingService $embedding)
    {
    }

    /**
     * Set sensible defaults before the record is inserted.
     */
    public function creating(Post $post): void
    {
        $post->status ??= 'draft';
    }

    /**
     * After a post is saved, generate its embedding in the background
     * using defer() so the HTTP response is never blocked.
     *
     * Conditions that must ALL be true before we attempt embedding:
     *   - post must be published
     *   - on updates, at least title or content must have changed
     */
    public function saved(Post $post): void
    {
        if ($post->status !== 'published') {
            return;
        }

        $isUpdate = !$post->wasRecentlyCreated;

        if ($isUpdate && !$post->wasChanged(['title', 'content'])) {
            return;
        }

        // Clear the stale vector so embedPost() always regenerates a fresh one
        if ($isUpdate) {
            $post->updateQuietly(['embedding' => null, 'embedded_at' => null]);
            $post->embedding = null;
        }

        // Capture the ID only — avoid passing the full model into the closure
        // since the model state may be stale by the time defer() runs
        $postId = $post->id;

        // defer() runs after the HTTP response has been sent to the client,
        // so the user never waits for the embedding API call
        defer(function () use ($postId) {

            Log::info('[PostObserver][defer] fired', ['post_id' => $postId]);

            $fresh = \App\Models\Post::find($postId);

            if (!$fresh) {
                Log::warning('[PostObserver][defer] post not found', ['post_id' => $postId]);
                return;
            }

            $vector = app(EmbeddingService::class)->embedPost($fresh);

            if (empty($vector)) {
                Log::warning('[PostObserver][defer] embedding failed', ['post_id' => $postId]);
            } else {
                Log::info('[PostObserver][defer] embedding saved', ['post_id' => $postId]);
            }

            if (!$fresh->added_to_ai_at) {
                $aiService = app(AddPostToAI::class);
                $aiResult = $aiService->addPostToModel($fresh);

                if ($aiResult) {
                    $fresh->updateQuietly(['added_to_ai_at' => now()]);
                    Log::info('[PostObserver][defer] post added to AI model', ['post_id' => $postId]);
                } else {
                    Log::warning('[PostObserver][defer] failed to add post to AI model', ['post_id' => $postId]);
                }
            } else {
                Log::info('[PostObserver][defer] post already added to AI model, skipping', ['post_id' => $postId]);
            }
        });
    }

    /**
     * Clean up embedding cache entries when a post is deleted.
     */
    public function deleted(Post $post): void
    {
        cache()->forget('emb:post:' . $post->id);
        cache()->forget('emb:' . md5($post->title . ' ' . ($post->content ?? '')));

        Log::notice('[PostObserver] Post deleted', [
            'post_id' => $post->id,
            'title' => $post->title,
        ]);
    }

    public function restored(Post $post): void
    {
    }
}
