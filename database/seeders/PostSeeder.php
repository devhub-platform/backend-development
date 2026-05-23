<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'title' => 'How to Build a Laravel API with Sanctum',
                'content' => 'In this tutorial, we will learn how to build a secure API using Laravel Sanctum. We will cover authentication, token management, and best practices for API development.',
                'user_id' => 1,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
                'views' => 10,
                'read_time' => 10,
            ],
            [
                'title' => 'Laravel 10: New Features and Improvements',
                'content' => 'Laravel 10 is a major release that introduces a number of new features and improvements. In this post, we will explore the most notable changes and improvements.',
                'user_id' => 2,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
                'views' => 20,
                'read_time' => 15,
            ],
            [
                'title' => 'Introduction to Vue.js',
                'content' => 'Vue.js is a popular JavaScript framework for building user interfaces. In this post, we will explore the basics of Vue.js and its components.',
                'user_id' => 3,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
                'read_time' => 12,
            ],
            [
                'title' => 'How to Use Laravel Mix for Building Web Applications',
                'content' => 'Laravel Mix is a powerful tool for compiling and bundling JavaScript and CSS assets in Laravel. In this post, we will explore how to use it to build web applications.',
                'user_id' => 4,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
                'read_time' => 10,
            ]

        ];
        // Create 200 posts
//        Post::factory(200)->create();
//
//        // Attach random tags to each post
//        $tagIds = Tag::pluck('id')->toArray();
//
//        if (!empty($tagIds)) {
//            Post::all()->each(function ($post) use ($tagIds) {
//                $randomTags = collect($tagIds)->shuffle()->take(rand(1, 5));
//                $post->tags()->syncWithoutDetaching($randomTags->toArray());
//            });
//        }
//
//        $this->command->info('Posts seeded with tags.');
    }
}
