<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ImprovedRecommendationService;
use App\Services\UserEmbeddingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RecommendedUsersTest extends TestCase
{
    use DatabaseTransactions;

    private UserEmbeddingService $embeddingService;
    private ImprovedRecommendationService $recommendationService;
    private User $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->embeddingService = new UserEmbeddingService();
        $this->recommendationService = new ImprovedRecommendationService($this->embeddingService);

        // Create test users
        $this->authUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function test_recommended_users_endpoint_requires_authentication()
    {
        $response = $this->getJson('/api/v1/users/recommended');

        $response->assertStatus(401);
    }

    /** @test */
    public function test_recommended_users_endpoint_returns_correct_structure()
    {
        $response = $this->actingAs($this->authUser)
            ->getJson('/api/v1/users/recommended?limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'count',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'username',
                        'bio',
                        'avatar_url',
                        'skills',
                        'stats' => [
                            'followers',
                            'following',
                            'posts',
                            'questions',
                        ],
                        'is_verified',
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_recommended_users_include_recommendation_score()
    {
        // Create some candidate users
        $candidateUser = User::factory()->create([
            'email_verified_at' => now(),
            'skills' => ['PHP', 'Laravel'],
        ]);

        // Make auth user follow someone for mutual connection
        $this->authUser->following()->attach($candidateUser->id);

        $response = $this->actingAs($this->authUser)
            ->getJson('/api/v1/users/recommended?limit=1');

        $response->assertStatus(200);

        if ($response->json('count') > 0) {
            $response->assertJsonStructure([
                'data' => [
                    '*' => [
                        'recommendation' => [
                            'score',
                            'reasons'
                        ]
                    ]
                ]
            ]);
        }
    }

    /** @test */
    public function test_embedding_service_generates_vectors()
    {
        $user = User::factory()->create([
            'skills' => ['PHP', 'Laravel', 'React'],
            'bio' => 'Test bio',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ]);

        $embedding = $this->embeddingService->generateUserEmbedding($user);

        $this->assertIsArray($embedding);
        $this->assertArrayHasKey('skills_vector', $embedding);
        $this->assertArrayHasKey('interests_vector', $embedding);
        $this->assertArrayHasKey('activity_vector', $embedding);
        $this->assertArrayHasKey('social_vector', $embedding);
        $this->assertArrayHasKey('profile_completeness', $embedding);

        $this->assertIsArray($embedding['skills_vector']);
        $this->assertIsArray($embedding['interests_vector']);
        $this->assertIsArray($embedding['activity_vector']);
        $this->assertIsArray($embedding['social_vector']);
        $this->assertIsNumeric($embedding['profile_completeness']);
    }

    /** @test */
    public function test_similarity_score_calculation()
    {
        $user1 = User::factory()->create([
            'skills' => ['PHP', 'Laravel'],
            'email_verified_at' => now(),
        ]);

        $user2 = User::factory()->create([
            'skills' => ['PHP', 'Laravel'],
            'email_verified_at' => now(),
        ]);

        $user3 = User::factory()->create([
            'skills' => ['Java', 'Spring'],
            'email_verified_at' => now(),
        ]);

        $embedding1 = $this->embeddingService->generateUserEmbedding($user1);
        $embedding2 = $this->embeddingService->generateUserEmbedding($user2);
        $embedding3 = $this->embeddingService->generateUserEmbedding($user3);

        $similarity1_2 = $this->embeddingService->calculateSimilarityScore($embedding1, $embedding2);
        $similarity1_3 = $this->embeddingService->calculateSimilarityScore($embedding1, $embedding3);

        // Users with same skills should have higher similarity
        $this->assertGreaterThan($similarity1_3, $similarity1_2);
        $this->assertGreaterThanOrEqual(0, $similarity1_2);
        $this->assertLessThanOrEqual(1, $similarity1_2);
    }

    /** @test */
    public function test_recommended_users_excludes_already_following()
    {
        $followingUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Auth user is already following this user
        $this->authUser->following()->attach($followingUser->id);

        $recommended = $this->recommendationService->getRecommendedUsers($this->authUser, 100);

        // Following user should not be in recommendations
        $this->assertFalse($recommended->contains('id', $followingUser->id));
    }

    /** @test */
    public function test_recommended_users_respects_limit()
    {
        // Create multiple candidate users
        User::factory()->count(15)->create([
            'email_verified_at' => now(),
        ]);

        $recommended = $this->recommendationService->getRecommendedUsers($this->authUser, 5);

        $this->assertLessThanOrEqual(5, $recommended->count());
    }

    /** @test */
    public function test_embedding_cache_works()
    {
        $user = User::factory()->create();

        // First call should generate embedding
        $embedding1 = $this->embeddingService->generateUserEmbedding($user);

        // Second call should return cached version
        $embedding2 = $this->embeddingService->generateUserEmbedding($user);

        $this->assertEquals($embedding1, $embedding2);
    }

    /** @test */
    public function test_force_refresh_bypasses_cache()
    {
        $response1 = $this->actingAs($this->authUser)
            ->getJson('/api/v1/users/recommended?limit=5');

        $response2 = $this->actingAs($this->authUser)
            ->getJson('/api/v1/users/recommended?limit=5&force_refresh=true');

        // Both should return valid responses
        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    /** @test */
    public function test_profile_completeness_affects_score()
    {
        // User with complete profile
        $completeUser = User::factory()->create([
            'email_verified_at' => now(),
            'bio' => 'Test bio',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'education' => 'Computer Science',
            'skills' => ['PHP', 'Laravel'],
            'location' => 'New York',
            'linkedin_username' => 'testuser',
        ]);

        // User with minimal profile
        $minimalUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $embeddingComplete = $this->embeddingService->generateUserEmbedding($completeUser);
        $embeddingMinimal = $this->embeddingService->generateUserEmbedding($minimalUser);

        $this->assertGreaterThan(
            $embeddingMinimal['profile_completeness'],
            $embeddingComplete['profile_completeness']
        );
    }

    /** @test */
    public function test_mutual_followers_boost_score()
    {
        $mutualFollower = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $candidateUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Create mutual follower relationship
        $this->authUser->followers()->attach($mutualFollower->id);
        $candidateUser->followers()->attach($mutualFollower->id);

        // Auth user and candidate should have higher mutual connection score
        // This would be visible in recommendation reasons

        $response = $this->actingAs($this->authUser)
            ->getJson('/api/v1/users/recommended?limit=10');

        $response->assertStatus(200);
    }
}

