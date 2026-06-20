<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Comment;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $posts = Post::where('status', 'published')->get();

        foreach ($posts as $post) {

            Comment::factory()
                ->count(rand(0, 10))
                ->create([
                    'post_id' => $post->id,
                ]);
        }

        $this->command->info('Comments seeded.');
    }
}
