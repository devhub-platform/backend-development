<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        // Create 200 posts
        Post::factory(200)->create();

        // Attach random tags to each post
        $tagIds = Tag::pluck('id')->toArray();

        if (!empty($tagIds)) {
            Post::all()->each(function ($post) use ($tagIds) {
                $randomTags = collect($tagIds)->shuffle()->take(rand(1, 5));
                $post->tags()->syncWithoutDetaching($randomTags->toArray());
            });
        }

        $this->command->info('Posts seeded with tags.');
    }
}
