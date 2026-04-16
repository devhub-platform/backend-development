<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Question;
use App\Models\QuestionView;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsService
{
    public function overview(): array
    {
        $today = now()->startOfDay();

        $totalPosts = Post::query()->count();
        $publishedPosts = Post::query()->where('status', 'published')->count();
        $draftPosts = Post::query()->where('status', 'draft')->count();
        $totalQuestions = Question::query()->count();
        $resolvedQuestions = Question::query()->where('is_resolved', true)->count();
        $postViews = DB::table('post_views')->count();
        $questionViews = QuestionView::query()->count();
        $totalUsers = User::query()->count();
        $activeToday = User::query()->where('last_seen_at', '>=', $today)->count();

        return [
            'total_users' => $totalUsers,
            'active_today' => $activeToday,
            'total_posts' => $totalPosts,
            'published_posts' => $publishedPosts,
            'draft_posts' => $draftPosts,
            'archived_posts' => $totalPosts - $publishedPosts - $draftPosts,
            'total_questions' => $totalQuestions,
            'resolved_questions' => $resolvedQuestions,
            'unanswered_questions' => $totalQuestions - $resolvedQuestions,
            'post_views' => $postViews,
            'question_views' => $questionViews,
            'total_views' => $postViews + $questionViews,
        ];
    }

    public function contentTrend(int $days = 14): array
    {
        $days = max(1, $days);
        $start = now()->startOfDay()->subDays($days - 1);

        $postsByDay = $this->aggregateByDate('posts', 'created_at', $start);
        $questionsByDay = $this->aggregateByDate('questions', 'created_at', $start);
        $postViewsByDay = $this->aggregateByDate('post_views', 'viewed_at', $start);
        $questionViewsByDay = $this->aggregateByDate('question_views', 'viewed_at', $start);

        $labels = [];
        $posts = [];
        $questions = [];
        $views = [];

        for ($date = $start->copy(); $date->lte(now()); $date->addDay()) {
            $key = $date->toDateString();

            $labels[] = $date->format('M d');
            $posts[] = $postsByDay[$key] ?? 0;
            $questions[] = $questionsByDay[$key] ?? 0;
            $views[] = ($postViewsByDay[$key] ?? 0) + ($questionViewsByDay[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'posts' => $posts,
            'questions' => $questions,
            'views' => $views,
        ];
    }

    public function topPosts(int $limit = 5)
    {
        return Post::query()
            ->with('user:id,name')
            ->select(['id', 'user_id', 'title', 'slug', 'views', 'status', 'created_at'])
            ->orderByDesc('views')
            ->limit(max(1, $limit))
            ->get();
    }

    private function aggregateByDate(string $table, string $dateColumn, Carbon $start): array
    {
        return DB::table($table)
            ->selectRaw("DATE($dateColumn) as day, COUNT(*) as total")
            ->where($dateColumn, '>=', $start)
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($value): int => (int) $value)
            ->all();
    }
}

