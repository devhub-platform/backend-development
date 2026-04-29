<?php

namespace App\Providers;

use App\Models\Answer;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Question;
use App\Models\ReadingList;
use App\Models\Report;
use App\Models\Topic;
use App\Models\User;
use App\Policies\AnswerPolicy;
use App\Policies\ChatPolicy;
use App\Policies\CommentPolicy;
use App\Policies\PostPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\ReadingListPolicy;
use App\Policies\ReportPolicy;
use App\Policies\TopicPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Musonza\Chat\Models\Conversation;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Post::class => PostPolicy::class,
        Comment::class => CommentPolicy::class,
        ReadingList::class => ReadingListPolicy::class,
        Conversation::class => ChatPolicy::class,
        Question::class => QuestionPolicy::class,
        Answer::class => AnswerPolicy::class,
        Topic::class => TopicPolicy::class,
        User::class => UserPolicy::class,
        Report::class => ReportPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Define the gate for Log Viewer access
        // Allow any authenticated user to access Log Viewer
        Gate::define('viewLogViewer', function (User $user) {
            return true;
        });
    }
}
