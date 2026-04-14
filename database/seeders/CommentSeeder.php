<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $posts = Post::where('status', 'published')->pluck('id');

        // Each post gets 0-20 comments
        $posts->each(function ($postId) {
            $count = rand(0, 20);
            if ($count > 0) {
                Comment::factory()->count($count)->create(['post_id' => $postId]);
            }
        });

        $this->command->info('Comments seeded.');
    }
}
