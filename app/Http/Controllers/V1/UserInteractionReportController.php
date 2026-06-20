<?php

namespace App\Http\Controllers\V1;

use App\Services\UserInterestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserInteractionReportController
{
    protected UserInterestService $userInterestService;

    public function __construct(UserInterestService $userInterestService)
    {
        $this->userInterestService = $userInterestService;
    }

    public function getInteractionHistory(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $limit = $request->query('limit', 50);
        $history = $this->userInterestService->getUserTopicInteractionHistory($user->id, $limit);

        return response()->json([
            'status' => true,
            'user_id' => $user->id,
            'message' => 'Your topic interaction history',
            'summary' => [
                'total_unique_topics_interacted' => count($history),
                'topics_subscribed' => count(array_filter($history, fn($t) => $t['is_subscribed'])),
                'topics_for_consideration' => count(array_filter($history, fn($t) => $t['auto_add_eligible'])),
            ],
            'data' => $history
        ]);
    }

    public function getInteractionBreakdown(int $topicId): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $breakdown = $this->userInterestService->getTopicInteractionBreakdown($user->id, $topicId);

        if (empty($breakdown)) {
            return response()->json([
                'message' => 'No interactions found for this topic'
            ], 404);
        }

        $totalScore = array_reduce($breakdown, fn($sum, $b) => $sum + $b['total_score'], 0);

        return response()->json([
            'status' => true,
            'user_id' => $user->id,
            'topic_id' => $topicId,
            'message' => 'Interaction breakdown by type',
            'summary' => [
                'total_interactions' => array_reduce($breakdown, fn($sum, $b) => $sum + $b['count'], 0),
                'total_engagement_score' => (float) $totalScore,
                'types_of_interactions' => count($breakdown),
            ],
            'breakdown' => $breakdown
        ]);
    }

    /**
     * Get recommended topics based on interaction history
     * Shows topics user has interacted with but hasn't subscribed to yet
     *
     * GET /api/v1/user/recommended-topics
     */
    public function getRecommendedTopics(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $limit = $request->query('limit', 10);
        $recommended = $this->userInterestService->getRecommendedTopics($user->id, $limit);

        if (empty($recommended)) {
            return response()->json([
                'status' => true,
                'user_id' => $user->id,
                'message' => 'No topic recommendations at this time',
                'data' => []
            ]);
        }

        return response()->json([
            'status' => true,
            'user_id' => $user->id,
            'message' => 'Recommended topics based on your activity',
            'summary' => [
                'recommended_count' => count($recommended),
                'action_needed' => 'Review and subscribe to topics of interest'
            ],
            'data' => $recommended
        ]);
    }

    /**
     * Get detailed topic interaction report
     * Comprehensive report for a specific topic including all metrics
     *
     * GET /api/v1/user/topic-report/{topicId}
     */
    public function getTopicReport(int $topicId): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $history = $this->userInterestService->getUserTopicInteractionHistory($user->id, 100);
        $topicData = collect($history)->firstWhere('topic_id', $topicId);

        if (!$topicData) {
            return response()->json([
                'message' => 'No interaction data found for this topic'
            ], 404);
        }

        $breakdown = $this->userInterestService->getTopicInteractionBreakdown($user->id, $topicId);

        return response()->json([
            'status' => true,
            'user_id' => $user->id,
            'topic_id' => $topicId,
            'topic_name' => $topicData['topic_name'],
            'message' => 'Detailed topic interaction report',
            'report' => [
                'overview' => [
                    'total_interactions' => $topicData['reported_interaction_count'],
                    'engagement_score' => $topicData['engagement_score'],
                    'last_interaction' => $topicData['last_interaction_at'],
                    'is_subscribed' => $topicData['is_subscribed'],
                    'eligible_for_auto_subscription' => $topicData['auto_add_eligible'],
                ],
                'interaction_breakdown' => $breakdown,
                'recommendation' => $this->generateReport($topicData),
            ]
        ]);
    }

    /**
     * Generate AI-like recommendation text
     */
    private function generateReport(array $topicData): array
    {
        $score = $topicData['engagement_score'];
        $interactions = $topicData['reported_interaction_count'];

        if ($topicData['is_subscribed']) {
            return [
                'status' => 'Subscribed',
                'message' => "You're already following this topic with {$interactions} interactions.",
            ];
        }

        if ($score >= 15) {
            return [
                'status' => 'Highly Recommended',
                'message' => "Based on your {$interactions} interactions (score: {$score}), you should subscribe to this topic.",
                'action' => 'subscribe_now',
            ];
        } elseif ($score >= 8) {
            return [
                'status' => 'Recommended',
                'message' => "Your interactions ({$interactions} times, score: {$score}) show good interest in this topic.",
                'action' => 'consider_subscribing',
            ];
        } else {
            return [
                'status' => 'Not Recommended Yet',
                'message' => "Limited interactions ({$interactions} times, score: {$score}) with this topic.",
                'action' => 'continue_exploring',
            ];
        }
    }

    /**
     * Get all user analytics
     * Comprehensive view of user's entire interaction profile
     *
     * GET /api/v1/user/interaction-analytics
     */
    public function getUserAnalytics(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $history = $this->userInterestService->getUserTopicInteractionHistory($user->id, 100);
        $recommended = $this->userInterestService->getRecommendedTopics($user->id, 10);

        $topicsSubscribed = collect($history)->filter(fn($t) => $t['is_subscribed'])->values();
        $topicsInteracting = collect($history)->filter(fn($t) => !$t['is_subscribed'])->values();

        $totalScore = collect($history)->sum('engagement_score');
        $totalInteractions = collect($history)->sum('reported_interaction_count');

        return response()->json([
            'status' => true,
            'user_id' => $user->id,
            'message' => 'Your interaction analytics',
            'analytics' => [
                'overall' => [
                    'topics_subscribed' => $topicsSubscribed->count(),
                    'topics_interacting_with' => $topicsInteracting->count(),
                    'total_interactions_tracked' => $totalInteractions,
                    'total_engagement_score' => (float) $totalScore,
                    'average_score_per_topic' => $topicsInteracting->count() > 0
                        ? (float)($totalScore / ($topicsSubscribed->count() + $topicsInteracting->count()))
                        : 0,
                ],
                'top_topics' => collect($history)
                    ->sortByDesc('engagement_score')
                    ->take(5)
                    ->values(),
                'recommended_for_subscription' => array_slice($recommended, 0, 5),
                'interaction_summary' => [
                    'subscribed_count' => $topicsSubscribed->count(),
                    'not_subscribed_but_interacting' => $topicsInteracting->count(),
                    'recommended_but_not_yet_interacted' => 'Check recommended_for_subscription',
                ],
            ]
        ]);
    }
}

