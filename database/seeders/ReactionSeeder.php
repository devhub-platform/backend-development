<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReactionSeeder extends Seeder
{
    public function run(): void
    {
        $posts   = Post::where('status', 'published')->pluck('id')->toArray();
        $userIds = User::pluck('id')->toArray();
        $types   = ['like', 'love', 'unicorn', 'exploding_head', 'raised_hands', 'fire'];

        $reactions = [];

        foreach ($posts as $postId) {
            // Random number of reactions per post (0 to 50)
            $count = rand(0, 50);

            $usedUsers = collect($userIds)->shuffle()->take($count);

            foreach ($usedUsers as $userId) {
                $reactions[] = [
                    'user_id'        => $userId,
                    'reactable_type' => 'App\\Models\\Post',
                    'reactable_id'   => $postId,
                    'type'           => $types[array_rand($types)],
                    'ip'             => fake()->ipv4(),
                    'created_at'     => now()->subHours(rand(0, 168)),
                    'updated_at'     => now(),
                ];
            }
        }

        // Insert in chunks to avoid memory issues
        collect($reactions)->chunk(500)->each(function ($chunk) {
            DB::table('reactions')->insertOrIgnore($chunk->toArray());
        });

        $this->command->info('Reactions seeded: ' . count($reactions));
    }
}
