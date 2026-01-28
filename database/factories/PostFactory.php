<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'slug' => fake()->unique()->slug(),
            'image_url' => fake()->imageUrl(640, 480, 'nature'),
            'content' => fake()->paragraphs(3, true),
            'created_at' => now(),
            'updated_at' => now(),
            'status' => 'published',
            'read_time' => fake()->numberBetween(1, 10)
        ];
    }
}
