<?php

namespace App\Services\AI;

class TopicDetector
{
    private const MAP = [
        'ai' => ['ai','ml','llm','gpt','embedding','neural'],
        'security' => ['vulnerability','cve','xss','exploit','security'],
        'web' => ['react','vue','angular','api','frontend','backend'],
        'devops' => ['docker','kubernetes','aws','ci/cd','deployment'],
        'systems' => ['linux','database','redis','performance'],
    ];

    public function detect(array $item): string
    {
        $text = strtolower(
            ($item['title'] ?? '') . ' ' .
            ($item['content'] ?? '') . ' ' .
            implode(' ', $item['tags'] ?? [])
        );

        $best = 'general';
        $score = 0;

        foreach (self::MAP as $topic => $keys) {
            $tmp = 0;

            foreach ($keys as $k) {
                if (str_contains($text, $k)) {
                    $tmp += strlen($k);
                }
            }

            if ($tmp > $score) {
                $score = $tmp;
                $best = $topic;
            }
        }

        return $best;
    }

    public function detectBatch(array $items): array
    {
        return array_map(function ($i) {
            $i['topic'] = $this->detect($i);
            return $i;
        }, $items);
    }
}
