<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostObserver
{
    public function creating(Post $post): void
    {
        $post->title = Str::title($post->title);
    }

    public function created(Post $post): void
    {
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
