<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
//            UserSeeder::class,
//            TagSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
            ReactionSeeder::class,
//            QuestionSeeder::class,
//            AnswerSeeder::class,
//            QuestionVoteSeeder::class,
//            AnswerVoteSeeder::class,
//            QuestionViewSeeder::class,
        ]);
    }
}
