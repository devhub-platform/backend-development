<?php

namespace Database\Seeders;

use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topics = [
            [
                'name' => 'Backend Development',
                'description' => 'Server-side development, APIs, databases, and backend architecture',
                'icon' => '⚙️',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Frontend Development',
                'description' => 'Web UI, HTML, CSS, JavaScript, React, Vue, and modern frontend frameworks',
                'icon' => '🎨',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'DevOps',
                'description' => 'CI/CD, deployment, containerization, infrastructure, and system administration',
                'icon' => '🚀',
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Mobile Development',
                'description' => 'iOS, Android, React Native, Flutter, and cross-platform mobile apps',
                'icon' => '📱',
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Data Science',
                'description' => 'Machine learning, data analysis, AI, and big data technologies',
                'icon' => '📊',
                'display_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Cloud Computing',
                'description' => 'AWS, Azure, Google Cloud, and cloud platform services',
                'icon' => '☁️',
                'display_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Database Design',
                'description' => 'SQL, NoSQL, database optimization, and data modeling',
                'icon' => '🗄️',
                'display_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Web Security',
                'description' => 'Cybersecurity, authentication, encryption, and secure coding practices',
                'icon' => '🔒',
                'display_order' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Testing & QA',
                'description' => 'Unit testing, integration testing, automation, and quality assurance',
                'icon' => '✅',
                'display_order' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'Artificial Intelligence',
                'description' => 'AI models, LLMs, prompt engineering, and intelligent systems',
                'icon' => '🤖',
                'display_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Open Source',
                'description' => 'Contributing to open source projects and community development',
                'icon' => '🔓',
                'display_order' => 11,
                'is_active' => true,
            ],
            [
                'name' => 'Tech Career',
                'description' => 'Career advice, interviews, professional development, and job market trends',
                'icon' => '💼',
                'display_order' => 12,
                'is_active' => true,
            ],
        ];

        foreach ($topics as $topic) {
            Topic::firstOrCreate(['name' => $topic['name']], $topic);
        }
    }
}

