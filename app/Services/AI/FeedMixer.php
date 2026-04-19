<?php

namespace App\Services\AI;

/**
 * FeedMixer
 *
 * Deterministic diversity-aware ranking layer.
 *
 * Responsibilities:
 * - Enforce topic diversity constraints
 * - Preserve ranking signal (score)
 * - Ensure bounded output size
 * - Provide stable ordering across requests
 */
class FeedMixer
{
    private const MAX_PER_TOPIC = 6;
    private const FEED_SIZE     = 15;
    private const SHUFFLE_TOP_N = 3;

    /**
     * Mixes ranked items into a diversified feed.
     *
     * @param array $items Raw scored items with optional topic metadata
     * @return array Balanced and ranked subset of items
     */
    public function mix(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        /**
         * Normalize input contract
         * Ensures downstream stability (score/topic existence)
         */
        foreach ($items as &$item) {
            $item['score'] = (float) ($item['score'] ?? $item['trending_score'] ?? 0);
            $item['topic'] = $item['topic'] ?? 'general';
        }
        unset($item);

        /**
         * Partition by topic for diversity enforcement
         */
        $groups = [];

        foreach ($items as $item) {
            $groups[$item['topic']][] = $item;
        }

        /**
         * Intra-topic ranking + mild stochasticity for freshness
         */
        foreach ($groups as &$group) {

            usort($group, fn($a, $b) => $b['score'] <=> $a['score']);

            if (count($group) > self::SHUFFLE_TOP_N) {
                $head = array_slice($group, 0, self::SHUFFLE_TOP_N);
                shuffle($head);

                $group = array_merge(
                    $head,
                    array_slice($group, self::SHUFFLE_TOP_N)
                );
            }

            $group = array_slice($group, 0, self::MAX_PER_TOPIC);
        }
        unset($group);

        /**
         * Cross-topic interleaving
         * Ensures no single topic dominates early positions
         */
        $result = [];

        while (count($result) < self::FEED_SIZE) {

            $progress = false;

            foreach ($groups as &$group) {
                if (!empty($group)) {
                    $result[] = array_shift($group);
                    $progress = true;

                    if (count($result) >= self::FEED_SIZE) {
                        break 2;
                    }
                }
            }

            if (!$progress) {
                break;
            }
        }

        /**
         * Final ranking pass
         * Preserves relevance signal without destroying diversity
         */
        usort($result, fn($a, $b) =>
            ($b['score'] ?? 0) <=> ($a['score'] ?? 0)
        );

        return array_slice($result, 0, self::FEED_SIZE);
    }
}
