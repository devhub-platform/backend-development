<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class ProgrammingPostsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $topics = [
            'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'React', 'Vue', 'Node.js',
            'CSS', 'HTML', 'SQL', 'PostgreSQL', 'MySQL', 'Docker', 'Kubernetes',
            'DevOps', 'CI/CD', 'Testing', 'TDD', 'Design Patterns', 'OOP',
            'Functional Programming', 'Algorithms', 'Data Structures', 'Python',
            'Django', 'Go', 'Rust', 'Security', 'Performance', 'Machine Learning'
        ];

        // Try to pick an existing user; fall back to user_id = 1
        $defaultUserId = optional(User::inRandomOrder()->first())->id ?? 1;

        for ($i = 0; $i < 100; $i++) {
            $topic = $faker->randomElement($topics);
            $subtopic = $faker->words(rand(1,3), true);

            $title = ucfirst($faker->randomElement([
                "Understanding $topic: $subtopic",
                "$topic Best Practices: $subtopic",
                "$subtopic in $topic — A Practical Guide",
                "Getting Started with $topic: $subtopic",
                "Advanced $topic Techniques: $subtopic",
            ]));

            $content = implode("\n\n", $faker->paragraphs(rand(3, 8)));

            $wordCount = str_word_count(strip_tags($content));
            $readTime = max(1, (int) ceil($wordCount / 200));

            $slug = Str::slug($title . '-' . Str::substr((string) Str::uuid(), 0, 8));
            $uuid = (string) Str::uuid();

            $post = Post::create([
                'user_id' => $defaultUserId,
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'status' => 'published',
                'read_time' => $readTime,
                'uuid' => $uuid,
                'created_at' => now()->subDays(rand(0, 365)),
                'updated_at' => now(),
            ]);

            // Attach 1-3 programming tags (create if not exists)
            $tagNames = $faker->randomElements($topics, rand(1, 3));
            $tagIds = [];
            foreach ($tagNames as $name) {
                $tag = Tag::firstOrCreate(['name' => $name]);
                $tagIds[] = $tag->id;
            }

            if (!empty($tagIds)) {
                $post->tags()->syncWithoutDetaching($tagIds);
            }
        }

        $this->command->info('Seeded 100 programming posts.');
    }
}

