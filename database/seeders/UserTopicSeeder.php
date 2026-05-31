<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class UserTopicSeeder extends Seeder
{
    public function run(): void
    {
        $topicIds = Topic::pluck('id');

        User::all()->each(function ($user) use ($topicIds) {

            $user->topics()->syncWithoutDetaching(
                $topicIds->random(rand(2, 4))->toArray()
            );
        });

        $this->command->info('User topics seeded.');
    }
}
