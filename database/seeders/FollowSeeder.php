<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FollowSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {

            $following = $users
                ->where('id', '!=', $user->id)
                ->random(rand(5, 15))
                ->pluck('id');

            $user->following()->syncWithoutDetaching($following);
        }

        $this->command->info('Follows seeded.');
    }
}
