<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'post_id'    => Post::where('status', 'published')->inRandomOrder()->value('id') ?? Post::factory()->published(),
            'user_id'    => User::inRandomOrder()->value('id') ?? User::factory(),
            'content'    => $this->faker->paragraph(rand(1, 3)),
            'parent_id'  => null,
            'is_pinned'  => false,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function reply(): static
    {
        return $this->state(fn() => [
            'parent_id' => Comment::inRandomOrder()->value('id'),
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn() => ['is_pinned' => true]);
    }
}
