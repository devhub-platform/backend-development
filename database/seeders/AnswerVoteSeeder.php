<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\AnswerVote;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnswerVoteSeeder extends Seeder
{
    public function run(): void
    {
        $answers = Answer::all();
        $users   = User::take(10)->get();

        foreach ($answers as $answer) {
            foreach ($users->random(rand(2, 4)) as $user) {
                if ($user->id === $answer->user_id) continue;

                AnswerVote::firstOrCreate(
                    ['answer_id' => $answer->id, 'user_id' => $user->id],
                    ['vote_type' => rand(0, 1) ? 'upvote' : 'downvote']
                );
            }
        }

        $this->command->info('Answer votes seeded successfully!');
    }
}
