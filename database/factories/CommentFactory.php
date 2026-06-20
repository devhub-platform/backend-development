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
        $comments = [
            'Great explanation. Thanks for sharing.',
            'This solved a production issue I had recently.',
            'I think Redis caching would improve this workflow.',
            'Interesting approach. Have you tested scalability?',
            'Very clean implementation and architecture.',
            'This is one of the best tutorials I have read.',
            'Can you explain more about optimization strategies?',
            'I encountered similar issues in my Laravel project.',
            'Security considerations here are very important.',
            'This helped me improve application performance.',
        ];

        return [
            'post_id' => Post::inRandomOrder()->value('id'),
            'user_id' => User::inRandomOrder()->value('id'),
            'content' => fake()->randomElement($comments),
            'parent_id' => null,
            'is_pinned' => rand(1, 100) <= 3,
            'created_at' => now()->subDays(rand(0, 90)),
            'updated_at' => now(),
        ];
    }
}
