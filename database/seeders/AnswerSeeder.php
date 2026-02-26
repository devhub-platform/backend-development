<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnswerSeeder extends Seeder
{
    public function run(): void
    {
        $questions = Question::all();
        $users     = User::take(10)->get();

        if ($questions->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No questions or users found.');
            return;
        }

        $contents = [
            'You can solve this by using the built-in Laravel helpers and following the official documentation.',
            'The best approach here is to use eager loading with the with() method to avoid N+1 queries.',
            'I recommend using queues for this. Configure your QUEUE_CONNECTION in .env and implement ShouldQueue.',
            'Make sure to use caching with Redis or Memcached to improve performance significantly.',
            'The simplest solution is to use Laravel Sanctum for API authentication instead of JWT.',
            'Use database transactions to ensure data consistency when performing multiple operations.',
            'Consider using Laravel Scout with Algolia or Meilisearch for better search performance.',
            'You should implement rate limiting using the ThrottleRequests middleware.',
        ];

        foreach ($questions as $question) {
            $count = rand(2, 4);

            for ($i = 0; $i < $count; $i++) {
                $user = $users->random();

                Answer::firstOrCreate(
                    [
                        'question_id' => $question->id,
                        'user_id'     => $user->id,
                    ],
                    [
                        'content'       => $contents[array_rand($contents)],
                        'is_accepted'   => false,
                        'helpful_count' => rand(0, 15),
                    ]
                );
            }

            $question->update([
                'answers_count' => $question->answers()->count(),
            ]);
        }

        $this->command->info('Answers seeded successfully!');
    }
}
