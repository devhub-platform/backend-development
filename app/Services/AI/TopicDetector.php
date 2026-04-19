<?php

namespace App\Services\AI;

/**
 * TopicDetector
 *
 * Lightweight rule-based classifier for feed routing.
 *
 * Design choice:
 * - Zero runtime dependencies (no LLM / no API calls)
 * - Deterministic mapping for caching consistency
 * - Keyword precedence reflects topic specificity
 */
class TopicDetector
{
    /**
     * Ordered taxonomy.
     * Higher precision topics must appear first to avoid misclassification.
     */
    private const TOPIC_MAP = [
        'ai' => [
            'ai', 'ml', 'llm', 'gpt', 'machine learning', 'deep learning',
            'neural network', 'embedding', 'transformer', 'openai', 'rag'
        ],

        'security' => [
            'vulnerability', 'exploit', 'cve', 'xss', 'sql injection',
            'authentication', 'encryption', 'pentest', 'malware'
        ],

        'web' => [
            'react', 'vue', 'angular', 'javascript', 'typescript',
            'frontend', 'backend', 'api', 'graphql', 'node'
        ],

        'devops' => [
            'docker', 'kubernetes', 'ci/cd', 'terraform', 'aws',
            'deployment', 'pipeline', 'observability'
        ],

        'systems' => [
            'linux', 'kernel', 'concurrency', 'database',
            'redis', 'performance', 'memory'
        ],
    ];

    /**
     * Classifies a single item into a topic bucket.
     *
     * @param array $item
     * @return string
     */
    public function detect(array $item): string
    {
        $text = strtolower(implode(' ', [
            $item['title'] ?? '',
            implode(' ', $item['tags'] ?? []),
        ]));

        foreach (self::TOPIC_MAP as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $topic;
                }
            }
        }

        return 'general';
    }

    /**
     * Batch classification pass.
     *
     * @param array $items
     * @return array
     */
    public function detectBatch(array $items): array
    {
        return array_map(function ($item) {
            $item['topic'] = $this->detect($item);
            return $item;
        }, $items);
    }
}
