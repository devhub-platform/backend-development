<?php

namespace Database\Seeders;

use App\Models\AnswerVote;
use App\Models\Question;
use App\Models\QuestionVote;
use App\Models\QuestionView;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::take(5)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Run UserSeeder first.');
            return;
        }

        $questions = [
            [
                'title'   => 'How to implement JWT authentication in Laravel?',
                'content' => 'I am trying to implement JWT authentication in my Laravel API. I have installed the tymon/jwt-auth package but I am not sure how to configure it properly. Can someone walk me through the process?',
                'tags'    => ['laravel', 'jwt', 'authentication'],
            ],
            [
                'title'   => 'What is the difference between eager loading and lazy loading in Eloquent?',
                'content' => 'I keep hearing about N+1 problem in Laravel. Can someone explain the difference between eager loading and lazy loading, and when should I use each one?',
                'tags'    => ['laravel', 'eloquent', 'performance'],
            ],
            [
                'title'   => 'How to use queues in Laravel for sending emails?',
                'content' => 'My application sends a lot of emails and it is slowing down the response time. I heard that queues can help with this. How do I set up queues for email sending in Laravel?',
                'tags'    => ['laravel', 'queues', 'email'],
            ],
            [
                'title'   => 'Best practices for API versioning in Laravel?',
                'content' => 'I am building a REST API and I want to implement versioning. What are the best practices for API versioning in Laravel? Should I use URL versioning or header versioning?',
                'tags'    => ['laravel', 'api', 'rest'],
            ],
            [
                'title'   => 'How to optimize database queries in Laravel?',
                'content' => 'My Laravel application is getting slow because of too many database queries. What are the best techniques to optimize database queries and improve performance?',
                'tags'    => ['laravel', 'database', 'performance'],
            ],
        ];

        $answerContents = [
            'You can use the tymon/jwt-auth package. First run php artisan jwt:secret then configure your auth guard to use jwt driver in config/auth.php.',
            'Eager loading uses the with() method to load relationships in a single query. Lazy loading loads them on demand which causes the N+1 problem.',
            'Use the ShouldQueue interface on your Mailable class and configure your QUEUE_CONNECTION in .env to use database or redis.',
            'Consider using Laravel Sanctum instead of JWT for simpler API authentication with token management built in.',
            'Use database indexes on frequently queried columns and leverage Eloquent scopes to keep queries clean and reusable.',
        ];

        foreach ($questions as $i => $data) {
            $owner = $users[$i % $users->count()];
            $slug  = Str::slug($data['title']) . '-' . uniqid();

            $questionId = DB::table('questions')->insertGetId([
                'user_id'       => $owner->id,
                'title'         => $data['title'],
                'content'       => $data['content'],
                'slug'          => $slug,
                'is_resolved'   => false,
                'views'         => rand(10, 200),
                'answers_count' => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $question = Question::find($questionId);

            // ─── Tags ─────────────────────────────────────────────────────────
            // FIX: no slug column in tags table — insert name only
            if (!empty($data['tags'])) {
                $tagIds = collect($data['tags'])->map(function ($name) {
                    $name     = strtolower(trim($name));
                    $existing = DB::table('tags')->where('name', $name)->first();
                    if ($existing) return $existing->id;
                    return DB::table('tags')->insertGetId([
                        'name'       => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                })->toArray();

                $question->tags()->sync($tagIds);
            }

            // ─── Answers ──────────────────────────────────────────────────────
            $acceptedCount = 0;

            foreach (array_slice($answerContents, 0, 3) as $j => $content) {
                $answerer   = $users[($i + $j + 1) % $users->count()];
                $isAccepted = ($j === 0 && $i % 2 === 0);

                $answerId = DB::table('answers')->insertGetId([
                    'question_id'   => $question->id,
                    'user_id'       => $answerer->id,
                    'content'       => $content,
                    'is_accepted'   => $isAccepted,
                    'helpful_count' => rand(0, 20),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                if ($isAccepted) $acceptedCount++;

                // ─── Answer Votes ─────────────────────────────────────────────
                foreach ($users->take(3) as $voter) {
                    if ($voter->id === $answerer->id) continue;
                    AnswerVote::firstOrCreate(
                        ['answer_id' => $answerId, 'user_id' => $voter->id],
                        ['vote_type' => rand(0, 1) ? 'upvote' : 'downvote']
                    );
                }
            }

            // FIX: recalculate answers_count from DB
            $question->update([
                'answers_count' => $question->answers()->count(),
                'is_resolved'   => $acceptedCount > 0,
            ]);

            // ─── Question Votes ───────────────────────────────────────────────
            foreach ($users->take(3) as $voter) {
                if ($voter->id === $owner->id) continue;
                QuestionVote::firstOrCreate(
                    ['question_id' => $question->id, 'user_id' => $voter->id],
                    ['vote_type'   => rand(0, 1) ? 'upvote' : 'downvote']
                );
            }

            // ─── Question Views ───────────────────────────────────────────────
            foreach ($users as $viewer) {
                $view = QuestionView::firstOrCreate(
                    ['question_id' => $question->id, 'user_id' => $viewer->id],
                    ['viewed_at'   => now()->subMinutes(rand(1, 1000))]
                );
                if ($view->wasRecentlyCreated) {
                    $question->increment('views');
                }
            }
        }

        $this->command->info('Questions seeded successfully!');
    }
}
