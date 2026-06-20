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
        $posts = Post::where('status', 'published')->get();
        $users = User::pluck('id');

        $types = ['like','love','fire','unicorn','raised_hands','exploding_head'];

        $data = [];

        foreach ($posts as $post) {

            $count = rand(5, 40);

            $selectedUsers = $users->random(min($count, $users->count()));

            foreach ($selectedUsers as $userId) {

                $data[] = [
                    'user_id' => $userId,
                    'reactable_type' => Post::class,
                    'reactable_id' => $post->id,
                    'type' => $types[array_rand($types)],
                    'ip' => fake()->ipv4(),
                    'created_at' => now()->subDays(rand(0, 60)),
                    'updated_at' => now(),
                ];
            }
        }

        collect($data)->chunk(500)->each(function ($chunk) {
            DB::table('reactions')->insertOrIgnore($chunk->toArray());
        });

        $this->command->info('Reactions seeded.');
    }
}
