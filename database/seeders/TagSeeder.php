<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
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

        foreach ($tags as $tag) {
            Tag::firstOrCreate([
                'name' => $tag,
            ]);
        }

        $this->command->info('Tags seeded.');
    }
}
