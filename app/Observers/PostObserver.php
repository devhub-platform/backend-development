<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\AI\EmbeddingService;
use Illuminate\Support\Facades\Log;

class PostObserver
{
    public function __construct(private EmbeddingService $embedding) {}

    /**
     * Set sensible defaults before the record is inserted.
     */
    public function creating(Post $post): void
    {
        $post->status ??= 'draft';
    }

    /**
     * Embed the post whenever it is created or updated, but only when:
     *   - the post is published, AND
     *   - on updates, the embeddable fields actually changed.
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

        // Wipe the stale vector so embedPost() always regenerates a fresh one.
        if ($isUpdate) {
            $post->updateQuietly(['embedding' => null]);
            $post->embedding = null;
        }

        $success = $this->embedding->embedPost($post);

        if (!$success) {
            Log::warning('Post saved but embedding failed', [
                'post_id' => $post->id,
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Clean up the embedding cache when a post is removed.
     */
    public function deleted(Post $post): void
    {
        cache()->forget('emb:post:' . $post->id);

        Log::notice('Post deleted', [
            'post_id' => $post->id,
            'title'   => $post->title,
        ]);
    }

    public function restored(Post $post): void {}
}
