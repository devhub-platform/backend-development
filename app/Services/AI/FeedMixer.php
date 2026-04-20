<?php

namespace App\Services\AI;

class FeedMixer
{
    private const MAX_PER_TOPIC = 6;
    private const SIZE          = 15;
    private const SHUFFLE_TOP   = 3;

    public function mix(array $items): array
    {
        if (empty($items)) return [];

        foreach ($items as &$item) {
            $item['topic'] = $item['topic'] ?? 'general';
        }
        unset($item);

        $groups = [];

        foreach ($items as $item) {
            $groups[$item['topic']][] = $item;
        }

        foreach ($groups as &$group) {

            // sort ONLY by existing score (no override)
            usort($group, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

            if (count($group) > self::SHUFFLE_TOP) {
                $head = array_slice($group, 0, self::SHUFFLE_TOP);
                shuffle($head);

                $group = array_merge($head, array_slice($group, self::SHUFFLE_TOP));
            }

            $group = array_slice($group, 0, self::MAX_PER_TOPIC);
        }
        unset($group);

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

            if (!$progress) break;
        }

        // final sort (safe, based on score only)
        usort($result, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return array_slice($result, 0, self::SIZE);
    }
    public function mixWithSourceDiversity(array $items): array
    {
        $groups = [];

        foreach ($items as $item) {
            $source = $item['source'] ?? 'unknown';
            $groups[$source][] = $item;
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

            if (!$progress) break;
        }

        usort($result, fn($a, $b) =>
            ($b['score'] ?? 0) <=> ($a['score'] ?? 0)
        );

        return $result;
    }
}
