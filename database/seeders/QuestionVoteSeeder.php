<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionVote;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionVoteSeeder extends Seeder
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
            // FIX: guard against random() crashing when collection is smaller than requested count
            $count  = min(rand(2, 5), $users->count());
            $voters = $users->random($count);

            foreach ($voters as $user) {
                if ($user->id === $question->user_id) continue;

                QuestionVote::firstOrCreate(
                    ['question_id' => $question->id, 'user_id' => $user->id],
                    ['vote_type'   => rand(0, 1) ? 'upvote' : 'downvote']
                );
            }
        }

        $this->command->info('Question votes seeded successfully!');
    }
}
