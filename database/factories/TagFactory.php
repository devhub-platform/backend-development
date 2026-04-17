<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    // Real-world dev tags for more realistic data
    private array $devTags = [
        'laravel', 'php', 'javascript', 'typescript', 'react', 'vue',
        'nodejs', 'python', 'django', 'docker', 'kubernetes', 'aws',
        'devops', 'git', 'api', 'rest', 'graphql', 'mysql', 'redis',
        'mongodb', 'tailwind', 'css', 'html', 'linux', 'nginx',
        'testing', 'security', 'performance', 'clean-code', 'algorithms',
    ];

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement($this->devTags)
            ?? $this->faker->unique()->word();

        return [
            'name'       => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
