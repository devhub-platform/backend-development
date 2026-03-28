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
                'name' => 'UI/UX Design',
                'description' => 'User interface design, user experience, design systems, and visual aesthetics',
                'icon' => '🎨',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'React & Vue',
                'description' => 'Frontend frameworks, component libraries, state management, and modern web development',
                'icon' => '',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Python & Django',
                'description' => 'Python programming, Django framework, data science, and backend development',
                'icon' => '🐍',
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Flutter & Mobile',
                'description' => 'iOS, Android, React Native, Flutter, and cross-platform mobile apps',
                'icon' => '📱',
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Data Analytics & AI',
                'description' => 'Machine learning, data analysis, AI, and big data technologies',
                'icon' => '📊',
                'display_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'IOT & Cloud',
                'description' => 'Internet of Things, cloud computing, serverless architecture, and edge computing',
                'icon' => '☁️',
                'display_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'GIT & Version Control',
                'description' => 'Version control, GitHub, GitLab, and collaborative development workflows',
                'icon' => '🗄️',
                'display_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'PHP & Laravel',
                'description' => 'PHP programming, Laravel framework, backend development, and web applications',
                'icon' => '🐘',
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
                'name' => 'Computer Vision & LLMs',
                'description' => 'AI models, LLMs, prompt engineering, and intelligent systems',
                'icon' => '🤖',
                'display_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'HTML & CSS',
                'description' => 'Web fundamentals, responsive design, CSS frameworks, and frontend development',
                'icon' => '🌐',
                'display_order' => 11,
                'is_active' => true,
            ],
            [
                'name' => 'Insights & Careers',
                'description' => 'Career advice, interviews, professional development, and job market trends',
                'icon' => '💼',
                'display_order' => 12,
                'is_active' => true,
            ],
            [
                'name' => 'Open Source & Community',
                'description' => 'Open source projects, community building, and collaborative development',
                'icon' => '🌍',
                'display_order' => 13,
                'is_active' => true,
            ],
            [
                'name' => 'DevOps & Infrastructure',
                'description' => 'CI/CD, containerization, orchestration, and infrastructure management',
                'icon' => '⚙️',
                'display_order' => 14,
                'is_active' => true,
            ],
             [
                'name' => 'Security & Privacy',
                'description' => 'Cybersecurity, data privacy, secure coding practices, and threat mitigation',
                'icon' => '🔒',
                'display_order' => 15,
                'is_active' => true,
            ],[
                'name' => 'Game Development',
                'description' => 'Game design, development, engines, and interactive media',
                'icon' => '🎮',
                'display_order' => 16,
                'is_active' => true,
            ],
             [
                'name' => 'Blockchain & Crypto',
                'description' => 'Cryptocurrency, blockchain technology, smart contracts, and decentralized applications',
                'icon' => '⛓️',
                'display_order' => 17,
                'is_active' => true,
            ],
             [
                'name' => 'AR/VR & Metaverse',
                'description' => 'Augmented reality, virtual reality, metaverse development, and immersive technologies',
                'icon' => '🕶️',
                'display_order' => 18,
                'is_active' => true,
            ],
            [
                'name' => 'Hardware & Embedded',
                'description' => 'Embedded systems, microcontrollers, hardware programming, and IoT devices',
                'icon' => '🔧',
                'display_order' => 19,
                'is_active' => true,
            ],
             [
                'name' => 'Software Architecture',
                'description' => 'Design patterns, system architecture, scalability, and software engineering principles',
                'icon' => '🏗️',
                'display_order' => 20,
                'is_active' => true,
            ]
        ];

        foreach ($topics as $topic) {
            Topic::firstOrCreate(['name' => $topic['name']], $topic);
        }
    }
}

