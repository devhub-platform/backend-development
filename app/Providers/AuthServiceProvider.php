<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\ReadingList;
use App\Policies\CommentPolicy;
use App\Policies\ReadingListPolicy;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register()
    {

    }

    public function boot()
    {
    }

    protected $policies = [
        Post::class => PostPolicy::class,
        Comment::class => CommentPolicy::class,
        ReadingList::class => ReadingListPolicy::class
    ];
}
