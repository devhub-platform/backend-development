<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title     = $this->faker->sentence(rand(5, 10));
        $createdAt = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'user_id'     => User::inRandomOrder()->value('id') ?? User::factory(),
            'title'       => $title,
            'slug'        => Str::slug($title) . '-' . Str::random(5),
            'content'     => $this->faker->paragraphs(rand(3, 6), true),
            'cover_image' => null,
            'image_url'   => [
                $this->faker->imageUrl(640, 480, 'nature'),
                $this->faker->imageUrl(640, 480, 'technology'),
            ],
            'status'      => $this->faker->randomElement(['published', 'published', 'published', 'draft']), // 75% published
            'read_time'   => rand(1, 15),
            'views'       => $this->faker->numberBetween(0, 5000),
            'is_edit'     => false,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ];
    }

    public function published(): static
    {
        return $this->state(fn() => ['status' => 'published']);
    }

    public function draft(): static
    {
        return $this->state(fn() => ['status' => 'draft']);
    }

    public function trending(): static
    {
        return $this->state(fn() => [
            'views'      => $this->faker->numberBetween(1000, 10000),
            'status'     => 'published',
            'created_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
        ]);
    }
}
