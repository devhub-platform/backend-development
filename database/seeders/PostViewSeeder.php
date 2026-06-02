<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostViewSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        // مهم: chunk عشان ما نحملش كل posts في الذاكرة
        Post::select('id')
            ->chunk(100, function ($posts) use ($userIds) {

                $rows = [];

                foreach ($posts as $post) {

                    $count = rand(10, 50); // قلل الضغط (كان 200 😈)

                    $viewers = $userIds->random(min($count, $userIds->count()));

                    foreach ($viewers as $userId) {
                        $rows[] = [
                            'post_id' => $post->id,
                            'user_id' => $userId,
                            'viewed_at' => now()->subMinutes(rand(1, 5000)),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                DB::table('post_views')->insert($rows);
            });

        $this->command->info('Post views seeded successfully.');
    }
}
