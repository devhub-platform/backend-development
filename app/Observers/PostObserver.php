<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Log;
use App\Services\AI\AddPostToAI;

class PostObserver
{
    public function __construct(protected AddPostToAI $addPostToAIService)
    {
    }

    public function creating(Post $post): void
    {
//        $post->title = Str::title($post->title);
        $post->status = $post->status ?? 'draft';
    }

    public function created(Post $post): void
    {
        $aiAddResult = $this->addPostToAIService->addPostToModel($post);
        if (!$aiAddResult) {
            Log::warning('Post created but failed to add to AI model', [
                'post_id' => $post->id,
                'user_id' => auth()->id()
            ]);
        }
    }

    public function updated(Post $post): void
    {
    }

    public function deleted(Post $post): void
    {
        Log::notice('Post deleted', ['post_id' => $post->id, 'title' => $post->title]);
    }

    public function restored(Post $post): void
    {
    }
}
