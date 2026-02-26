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

        foreach ($questions as $question) {
            foreach ($users->random(rand(3, 8)) as $user) {
                $alreadyViewed = QuestionView::where('question_id', $question->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (!$alreadyViewed) {
                    QuestionView::create([
                        'question_id' => $question->id,
                        'user_id'     => $user->id,
                        'viewed_at'   => now()->subMinutes(rand(1, 10000)),
                    ]);
                    $question->increment('views');
                }
            }
        }

        $this->command->info('Question views seeded successfully!');
    }
}
