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
        $topics = [
            'Laravel',
            'React',
            'Docker',
            'Cyber Security',
            'DevOps',
            'Redis',
            'Node.js',
            'AI',
            'Python',
        ];

        $topic = fake()->randomElement($topics);

        $titles = [
            "Understanding {$topic} in Production",
            "{$topic} Best Practices",
            "Getting Started with {$topic}",
            "Advanced {$topic} Techniques",
            "Scaling Applications with {$topic}",
            "{$topic} Performance Optimization",
            "Common {$topic} Mistakes",
            "{$topic} Architecture Guide",
        ];

        $contentTemplates = [
            "In this article we explore {$topic} in modern scalable systems...",

            "{$topic} has become an essential part of modern software engineering...",

            "Modern applications rely heavily on {$topic} for scalability..."
        ];

        $title = fake()->randomElement($titles);
        $content = fake()->randomElement($contentTemplates);
        $isTrending = rand(1, 100) <= 15;

        return [
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'content' => $content,
            'cover_image' => null,
            'image_url' => [
                'https://picsum.photos/640/480?random=' . rand(1, 10000),
            ],
            'status' => fake()->randomElement(['published', 'published', 'published', 'draft']),
            'read_time' => rand(2, 15),
            'views' => $isTrending ? rand(5000, 50000) : rand(0, 3000),
            'is_edit' => false,
            'created_at' => now()->subDays(rand(0, 365)),
            'updated_at' => now()->subDays(rand(0, 30)),
        ];
    }

    public function published(): static
    {
        return $this->state(fn() => [
            'status' => 'published',
        ]);
    }

    public function trending(): static
    {
        return $this->state(fn() => [
            'views' => rand(10000, 50000),
            'status' => 'published',
        ]);
    }
}
