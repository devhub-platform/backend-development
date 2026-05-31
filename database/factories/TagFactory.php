<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    private array $tags = [
        'laravel',
        'php',
        'react',
        'vue',
        'javascript',
        'typescript',
        'nodejs',
        'python',
        'django',
        'docker',
        'kubernetes',
        'redis',
        'mysql',
        'postgresql',
        'graphql',
        'rest-api',
        'security',
        'xss',
        'jwt',
        'authentication',
        'clean-code',
        'architecture',
        'testing',
        'tdd',
        'performance',
        'ai',
        'machine-learning',
        'devops',
        'linux',
    ];

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement($this->tags),
        ];
    }
}
