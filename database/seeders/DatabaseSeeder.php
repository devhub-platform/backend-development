<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            QuestionSeeder::class,
            AnswerSeeder::class,
            AnswerVoteSeeder::class,
            QuestionVoteSeeder::class,
            QuestionViewSeeder::class,

        ]);
    }
}
