<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class SavedPostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = Post::pluck('id');

        User::all()->each(function ($user) use ($posts) {

            $user->savedPosts()->syncWithoutDetaching(
                $posts->random(rand(5, 20))->toArray()
            );
        });

        $this->command->info('Saved posts seeded.');
    }
}
