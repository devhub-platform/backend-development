<?php

namespace App\Providers;

use App\Models\Answer;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Question;
use App\Models\ReadingList;
use App\Policies\AnswerPolicy;
use App\Policies\ChatPolicy;
use App\Policies\CommentPolicy;
use App\Policies\PostPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\ReadingListPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Musonza\Chat\Models\Conversation;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Post::class         => PostPolicy::class,
        Comment::class      => CommentPolicy::class,
        ReadingList::class  => ReadingListPolicy::class,
        Conversation::class => ChatPolicy::class,
        Question::class     => QuestionPolicy::class,
        Answer::class       => AnswerPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
