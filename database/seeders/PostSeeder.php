<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $tags = Tag::all();

        for ($i = 0; $i < 200; $i++) {

            $post = Post::factory()->create();

            // attach tags
            $post->tags()->attach(
                $tags->random(rand(1, 4))->pluck('id')
            );
        }

        $this->command->info('Posts seeded.');
    }
}
