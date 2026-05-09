<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserInterestService
{
    private const MIN_INTERACTIONS_THRESHOLD = 1;

    private const INTERACTION_WEIGHTS = [
        'like' => 5,          // Strong signal
        'comment' => 10,       // Very strong signal
        'view' => 1,           // Weak signal
        'share' => 3,         // Very strong signal
        'save' => 4,          // Medium-strong signal
    ];

    private const MIN_SCORE_FOR_AUTO_ADD = 10;
    public function trackPostInteraction(User $user, Post $post, string $interactionType = 'view', ?string $metadata = null): array
    {
        try {
            $postTags = $post->tags()->get(['id', 'name']);

            if ($postTags->isEmpty()) {
                return ['tracked' => false, 'reason' => 'No tags on post'];
            }

            $topicInteractions = [];

            foreach ($postTags as $tag) {
                // Find matching topic
                $topic = Topic::where('name', $tag->name)
                    ->where('is_active', true)
                    ->first();

                if (!$topic) {
                    continue;
                }

                // Record interaction in topic_interactions table
                $this->recordInteraction(
                    $user->id,
                    $topic->id,
                    $post->id,
                    $interactionType,
                    $metadata
                );

                // Get user's current score for this topic
                $topicScore = $this->getUserTopicScore($user->id, $topic->id);
                $interactionCount = $this->getUserTopicInteractionCount($user->id, $topic->id);

                $topicInteractions[] = [
                    'topic_id' => $topic->id,
                    'topic_name' => $topic->name,
                    'interaction_type' => $interactionType,
                    'engagement_score' => (float) $topicScore,
                    'interaction_count' => $interactionCount,
                    'should_auto_add' => $this->shouldAutoAddTopic($user->id, $topic->id, $topicScore),
                ];
            }

            // Auto-add topics that meet threshold and user isn't already subscribed
            $topicsToAdd = array_filter(
                $topicInteractions,
                fn($t) => $t['should_auto_add'] && !$user->topics()->where('topic_id', $t['topic_id'])->exists()
            );

            if (!empty($topicsToAdd)) {
                $topicIds = array_column($topicsToAdd, 'topic_id');
                $user->topics()->attach($topicIds);
            }

            return [
                'tracked' => true,
                'interactions_recorded' => count($topicInteractions),
                'topic_interactions' => $topicInteractions,
                'topics_auto_added_count' => count($topicsToAdd),
                'topics_auto_added' => array_column($topicsToAdd, 'topic_name'),
            ];
        } catch (\Exception $e) {
            \Log::warning('Failed to track post interaction', [
                'user_id' => $user->id,
                'post_id' => $post->id,
                'interaction_type' => $interactionType,
                'error' => $e->getMessage()
            ]);

            return ['tracked' => false, 'reason' => $e->getMessage()];
        }
    }

    /**
     * Record individual interaction in database
     */
    private function recordInteraction(int $userId, int $topicId, int $postId, string $type, ?string $metadata): ?int
    {
        $weight = self::INTERACTION_WEIGHTS[$type] ?? 1;

        try {
            // Create table if doesn't exist (fallback)
            if (!DB::getSchemaBuilder()->hasTable('topic_user_interactions')) {
                $this->ensureInteractionTableExists();
            }

            return DB::table('topic_user_interactions')->insertGetId([
                'user_id' => $userId,
                'topic_id' => $topicId,
                'post_id' => $postId,
                'interaction_type' => $type,
                'weight' => $weight,
                'metadata' => $metadata,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not record interaction - table may not exist', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calculate user's engagement score for a topic
     */
    private function getUserTopicScore(int $userId, int $topicId): float
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('topic_user_interactions')) {
                return 0;
            }

            return (float) DB::table('topic_user_interactions')
                ->where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->sum('weight');
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get interaction count for a topic
     */
    private function getUserTopicInteractionCount(int $userId, int $topicId): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('topic_user_interactions')) {
                return 0;
            }

            return (int) DB::table('topic_user_interactions')
                ->where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Intelligent decision: Should topic be auto-added?
     */
    private function shouldAutoAddTopic(int $userId, int $topicId, float $score): bool
    {
        // Check if already subscribed
        if (DB::table('topic_user')
            ->where('user_id', $userId)
            ->where('topic_id', $topicId)
            ->exists()) {
            return false;
        }

        // Check score threshold
        return $score >= self::MIN_SCORE_FOR_AUTO_ADD;
    }

    /**
     * Get user's interaction history with topics
     * Shows report of all topics user has interacted with
     */
    public function getUserTopicInteractionHistory(int $userId, int $limit = 50): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('topic_user_interactions')) {
                return [];
            }

            $interactions = DB::table('topic_user_interactions')
                ->join('topics', 'topic_user_interactions.topic_id', '=', 'topics.id')
                ->select(
                    'topics.id',
                    'topics.name as topic_name',
                    DB::raw('COUNT(*) as interaction_count'),
                    DB::raw('SUM(topic_user_interactions.weight) as total_score'),
                    DB::raw('MAX(topic_user_interactions.created_at) as last_interaction')
                )
                ->where('topic_user_interactions.user_id', $userId)
                ->groupBy('topics.id', 'topics.name')
                ->orderByDesc('total_score')
                ->limit($limit)
                ->get()
                ->toArray();

            return array_map(function ($item) use ($userId) {
                $isSubscribed = DB::table('topic_user')
                    ->where('user_id', $userId)
                    ->where('topic_id', $item->id)
                    ->exists();

                return [
                    'topic_id' => $item->id,
                    'topic_name' => $item->topic_name,
                    'reported_interaction_count' => (int) $item->interaction_count,
                    'engagement_score' => (float) $item->total_score,
                    'last_interaction_at' => $item->last_interaction,
                    'is_subscribed' => $isSubscribed,
                    'auto_add_eligible' => (float) $item->total_score >= self::MIN_SCORE_FOR_AUTO_ADD && !$isSubscribed,
                ];
            }, $interactions);
        } catch (\Exception $e) {
            \Log::warning('Failed to get interaction history', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get breakdown of interaction types for a user and topic
     * Shows WHAT types of interactions (likes, views, etc.)
     */
    public function getTopicInteractionBreakdown(int $userId, int $topicId): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('topic_user_interactions')) {
                return [];
            }

            $breakdown = DB::table('topic_user_interactions')
                ->where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->select(
                    'interaction_type',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(weight) as score')
                )
                ->groupBy('interaction_type')
                ->get()
                ->toArray();

            return array_map(fn($b) => [
                'interaction_type' => $b->interaction_type,
                'count' => (int) $b->count,
                'total_score' => (float) $b->score,
                'weight_per_interaction' => self::INTERACTION_WEIGHTS[$b->interaction_type] ?? 1,
            ], $breakdown);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getRecommendedTopics(int $userId, int $limit = 10): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('topic_user_interactions')) {
                return [];
            }

            $recommended = DB::table('topic_user_interactions')
                ->join('topics', 'topic_user_interactions.topic_id', '=', 'topics.id')
                ->leftJoin('topic_user', function ($join) use ($userId) {
                    $join->on('topics.id', '=', 'topic_user.topic_id')
                        ->where('topic_user.user_id', '=', $userId);
                })
                ->select(
                    'topics.id',
                    'topics.name',
                    DB::raw('COUNT(topic_user_interactions.id) as interaction_count'),
                    DB::raw('SUM(topic_user_interactions.weight) as engagement_score'),
                    DB::raw('MAX(topic_user_interactions.created_at) as last_interaction'),
                    DB::raw('topic_user.user_id as is_subscribed')
                )
                ->where('topic_user_interactions.user_id', $userId)
                ->where('topic_user.user_id', null) // Not subscribed
                ->where('topics.is_active', true)
                ->groupBy('topics.id', 'topics.name', 'topic_user.user_id')
                ->orderByDesc('engagement_score')
                ->limit($limit)
                ->get()
                ->toArray();

            return array_map(fn($r) => [
                'topic_id' => $r->id,
                'topic_name' => $r->name,
                'interaction_count' => (int) $r->interaction_count,
                'engagement_score' => (float) $r->engagement_score,
                'last_interaction_at' => $r->last_interaction,
                'recommendation_reason' => $this->getRecommendationReason((float) $r->engagement_score, (int) $r->interaction_count),
                'action' => 'You should subscribe to this topic',
            ], $recommended);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate intelligent recommendation reason
     */
    private function getRecommendationReason(float $score, int $interactions): string
    {
        if ($score >= self::MIN_SCORE_FOR_AUTO_ADD) {
            if ($interactions >= 10) {
                return 'Highly recommended - You frequently interact with this topic';
            } elseif ($interactions >= 5) {
                return 'Recommended - You show strong interest in this topic';
            }
            return 'Good fit - Your interactions suggest interest in this topic';
        }
        return 'Based on your recent activity';
    }

    /**
     * Legacy method for backward compatibility
     */
    public function addTopicsFromPostInteraction(User $user, Post $post): void
    {
        $this->trackPostInteraction($user, $post, 'like');
    }

    /**
     * Legacy method for backward compatibility
     */
    public function addTopicsFromPostView(User $user, Post $post): void
    {
        $this->trackPostInteraction($user, $post, 'view');
    }

    /**
     * Ensure interaction tracking table exists
     */
    private function ensureInteractionTableExists(): void
    {
        // This is a fallback - run migration manually
        Log::info('topic_user_interactions table not found. Please run the migration.');
    }
}

