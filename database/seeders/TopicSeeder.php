<?php

namespace Database\Seeders;

use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    public function run(): void
    {
        $topics = [
            ['name' => 'Laravel', 'description' => 'Backend', 'icon' => '🐘', 'display_order' => 1, 'is_active' => true],
            ['name' => 'React', 'description' => 'Frontend', 'icon' => '⚛️', 'display_order' => 2, 'is_active' => true],
            ['name' => 'DevOps', 'description' => 'Infrastructure', 'icon' => '⚙️', 'display_order' => 3, 'is_active' => true],
            ['name' => 'Cyber Security', 'description' => 'Security', 'icon' => '🔒', 'display_order' => 4, 'is_active' => true],
            ['name' => 'AI & ML', 'description' => 'Artificial Intelligence', 'icon' => '🤖', 'display_order' => 5, 'is_active' => true],
            ['name' => 'Databases', 'description' => 'SQL & NoSQL', 'icon' => '🗄️', 'display_order' => 6, 'is_active' => true],
        ];

        foreach ($topics as $topic) {
            Topic::firstOrCreate(['name' => $topic['name']], $topic);
        }

        $this->command->info('Topics seeded.');
    }
}
