<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReactionFactory extends Factory
{
    protected $model =
        \Binafy\LaravelReaction\Models\Reaction::class;

    private array $types = [

        'like',
        'love',
        'fire',
        'unicorn',
        'raised_hands',
        'exploding_head',
    ];

    public function definition(): array
    {
        return [

            'user_id' =>
                User::inRandomOrder()->value('id'),

            'reactable_type' => Post::class,

            'reactable_id' =>
                Post::where('status', 'published')
                    ->inRandomOrder()
                    ->value('id'),

            'type' =>
                fake()->randomElement($this->types),

            'ip' => fake()->ipv4(),

            'created_at' =>
                now()->subDays(rand(0, 90)),

            'updated_at' => now(),
        ];
    }
}
