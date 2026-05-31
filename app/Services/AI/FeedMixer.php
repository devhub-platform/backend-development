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
        $groups = [];
        foreach ($items as $item) {
            $groups[$item['source'] ?? 'unknown'][] = $item;
        }

        $result = [];
        while (count($result) < 15) {
            $progress = false;

            foreach ($groups as &$group) {
                if (!empty($group)) {
                    $result[] = array_shift($group);
                    $progress = true;

                    if (count($result) >= 15) break 2;
                }
            }
            unset($group);

            if (!$progress) break;
        }

        usort($result, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return $result;
    }
}
