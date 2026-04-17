<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class ReactionFactory extends Factory
{
    protected $model = \Binafy\LaravelReaction\Models\Reaction::class;

    private array $types = [
        'like', 'love', 'unicorn', 'exploding_head', 'raised_hands', 'fire',
    ];

    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'user_id'        => User::inRandomOrder()->value('id') ?? User::factory(),
            'reactable_type' => Post::class,
            'reactable_id'   => Post::where('status', 'published')->inRandomOrder()->value('id') ?? Post::factory()->published(),
            'type'           => $this->faker->randomElement($this->types),
            'ip'             => $this->faker->ipv4(),
            'created_at'     => $createdAt,
            'updated_at'     => $createdAt,
        ];
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn() => [
            'reactable_type' => Post::class,
            'reactable_id'   => $post->id,
        ]);
    }
}
