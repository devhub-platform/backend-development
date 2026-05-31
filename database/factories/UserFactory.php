<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $skills = [
            'Laravel','PHP','JavaScript','TypeScript','React','Vue',
            'Node.js','Python','Docker','Cyber Security','DevOps',
            'AI','Machine Learning','MySQL','Redis',
        ];

        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'role' => 'user',
            'avatar_url' => 'https://i.pravatar.cc/300?img=' . rand(1, 70),
            'bio' => fake()->randomElement([
                'Backend developer passionate about scalable systems.',
                'Frontend engineer focused on performance and UX.',
                'Cybersecurity enthusiast and bug bounty hunter.',
                'Full-stack developer exploring AI integrations.',
                'Software engineer building modern web applications.',
            ]),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'created_at' => now()->subDays(rand(0, 365)),
            'updated_at' => now(),
        ];
    }
}
