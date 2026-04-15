<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Log;
use App\Services\AI\AddPostToAI;

class PostObserver
{
    public function creating(Post $post): void
    {
//        $post->title = Str::title($post->title);
        $post->status = $post->status ?? 'draft';
    }

    public function created(Post $post): void
    {
//        $ok = app(AddPostToAI::class)->addPostToModel($post);
//
//        if (! $ok) {
//            Log::warning('Post created but AI sync failed', ['post_id' => $post->id]);
//        }
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
