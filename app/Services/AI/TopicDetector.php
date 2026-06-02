<?php

namespace App\Services\AI;

class TopicDetector
{
    // Weighted keywords — higher weight = stronger signal for that topic
    private const MAP = [
        'ai' => [
            'llm' => 8, 'gpt' => 8, 'embedding' => 7, 'neural' => 7,
            'machine learning' => 7, 'deep learning' => 7, 'nlp' => 6,
            'transformer' => 6, 'diffusion' => 6, 'mistral' => 6,
            'claude' => 6, 'openai' => 6, 'gemini' => 6, 'ai' => 4,
            'ml' => 4, 'rag' => 5, 'vector' => 4, 'inference' => 5,
        ],
        'security' => [
            'vulnerability' => 8, 'exploit' => 8, 'cve' => 8,
            'xss' => 7, 'sql injection' => 7, 'zero day' => 7,
            'penetration' => 6, 'csrf' => 6, 'encryption' => 5,
            'auth' => 4, 'oauth' => 4, 'security' => 4,
        ],
        'web' => [
            'react' => 6, 'vue' => 6, 'angular' => 6, 'svelte' => 6,
            'next.js' => 6, 'nuxt' => 6, 'graphql' => 5, 'rest' => 4,
            'frontend' => 5, 'backend' => 5, 'typescript' => 5,
            'javascript' => 5, 'css' => 4, 'html' => 4, 'api' => 3,
        ],
        'devops' => [
            'kubernetes' => 7, 'docker' => 7, 'terraform' => 7,
            'ci/cd' => 7, 'deployment' => 6, 'nginx' => 6,
            'ansible' => 6, 'helm' => 6, 'devops' => 5,
            'cloud' => 4, 'server' => 3, 'aws' => 3,
            'linux' => 3,
        ],
        'systems' => [
            'postgresql' => 7, 'mysql' => 7, 'mongodb' => 7,
            'redis' => 6, 'database' => 5, 'performance' => 4,
            'cache' => 4, 'queue' => 4, 'memory' => 3,
        ],
        'mobile' => [
            'flutter' => 8, 'react native' => 8, 'swift' => 7,
            'kotlin' => 7, 'ios' => 6, 'android' => 6, 'mobile' => 5,
        ],
        'general' => [
            'rust' => 5, 'go' => 4, 'python' => 4, 'php' => 4,
            'django' => 5, 'laravel' => 5, 'clean-code' => 4,
            'architecture' => 4, 'patterns' => 4, 'programming' => 3,
            'software' => 3, 'developer' => 3, 'framework' => 3,
        ],
    ];

    // Keywords that need word boundaries (short/ambiguous — could match inside other words)
    private const BOUNDARY_REQUIRED = ['ai', 'ml', 'go', 'api', 'aws', 'ios', 'css', 'rag'];

    public function detect(array $item): string
    {
        $text = strtolower(
            ($item['title']       ?? '') . ' ' .
            ($item['content']     ?? '') . ' ' .
            ($item['description'] ?? '') . ' ' .
            implode(' ', $item['tags'] ?? [])
        );

        $scores = [];

        foreach (self::MAP as $topic => $keywords) {
            $total = 0;
            foreach ($keywords as $keyword => $weight) {
                $match = in_array($keyword, self::BOUNDARY_REQUIRED)
                    ? (bool) preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $text)
                    : str_contains($text, $keyword);

                if ($match) {
                    $total += $weight;
                }
            }
            $scores[$topic] = $total;
        }

        arsort($scores);
        $best = array_key_first($scores);

        return ($scores[$best] ?? 0) > 0 ? $best : 'general';
    }

    public function detectBatch(array $items): array
    {
        return array_map(function ($i) {
            $i['topic'] = $this->detect($i);
            return $i;
        }, $items);
    }
}
