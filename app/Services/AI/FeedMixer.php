<?php

namespace App\Services\AI;

class FeedMixer
{
    private const MAX_PER_TOPIC = 15;
    private const SIZE          = 50;

    public function mix(array $items): array
    {
        if (empty($items)) return [];

        // Ensure every item has a topic
        foreach ($items as &$item) {
            $item['topic'] = $item['topic'] ?? 'general';
        }
        unset($item);

        // Group by topic, sorted by score descending (no shuffle — keeps ranking honest)
        $groups = [];
        foreach ($items as $item) {
            $groups[$item['topic']][] = $item;
        }

        foreach ($groups as &$group) {
            usort($group, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
            $group = array_slice($group, 0, self::MAX_PER_TOPIC);
        }
        unset($group);

        // Round-robin across topics for diversity
        $result = [];
        while (count($result) < self::SIZE) {
            $progress = false;

            foreach ($groups as &$group) {
                if (!empty($group)) {
                    $result[] = array_shift($group);
                    $progress = true;

                    if (count($result) >= self::SIZE) break 2;
                }
            }
            unset($group);

            if (!$progress) break;
        }

        // Final sort by score
        usort($result, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return array_slice($result, 0, self::SIZE);
    }

    public function mixWithSourceDiversity(array $items): array
    {
        $total = 15;

        // Source caps: 40% github, 30% hackernews, 30% devto
        $caps = [
            'github'     => (int) ceil($total * 0.40), // 6
            'hackernews' => (int) ceil($total * 0.30), // 5
            'devto'      => (int) ceil($total * 0.30), // 5
        ];

        $groups = [];
        foreach ($items as $item) {
            $source = $item['source'] ?? 'unknown';
            $groups[$source][] = $item;
        }

        // Sort each group by score
        foreach ($groups as &$group) {
            usort($group, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));
        }
        unset($group);

        $result      = [];
        $sourceCounts = [];

        // Round-robin respecting caps
        while (count($result) < $total) {
            $progress = false;

            foreach ($groups as $source => &$group) {
                if (empty($group)) continue;

                $cap = $caps[$source] ?? (int) ceil($total * 0.30);
                if (($sourceCounts[$source] ?? 0) >= $cap) continue;

                $result[]                = array_shift($group);
                $sourceCounts[$source]   = ($sourceCounts[$source] ?? 0) + 1;
                $progress                = true;

                if (count($result) >= $total) break 2;
            }
            unset($group);

            if (!$progress) break;
        }

        usort($result, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return $result;
    }
}
