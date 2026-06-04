<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionView;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionViewSeeder extends Seeder
{
    public function run(): void
    {
        $questions = Question::all();
        $users     = User::take(10)->get();

        if ($questions->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No questions or users found.');
            return;
        }

        foreach ($questions as $question) {
            foreach ($users->random(rand(3, 8)) as $user) {
                // FIX: use firstOrCreate and check wasRecentlyCreated
                // to avoid double-incrementing views on re-runs
                $view = QuestionView::firstOrCreate(
                    [
                        'question_id' => $question->id,
                        'user_id'     => $user->id,
                    ],
                    [
                        'viewed_at' => now()->subMinutes(rand(1, 10000)),
                    ]
                );

                if ($view->wasRecentlyCreated) {
                    $question->increment('views');
                }
            }
        }

        $this->command->info('Question views seeded successfully!');
    }
}
