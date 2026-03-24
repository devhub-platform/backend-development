<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowersSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prioritizes_users_with_shared_topics_and_published_posts(): void
    {
        $authUser = User::factory()->create();
        $topic = Topic::create(['name' => 'Laravel']);

        $authUser->topics()->attach($topic->id);

        $bestMatch = User::factory()->create();
        $secondMatch = User::factory()->create();
        $fallbackCandidate = User::factory()->create();

        $bestMatch->topics()->attach($topic->id);
        $secondMatch->topics()->attach($topic->id);

        Post::factory()->count(3)->create(['user_id' => $bestMatch->id, 'status' => 'published']);
        Post::factory()->count(1)->create(['user_id' => $secondMatch->id, 'status' => 'published']);
        Post::factory()->count(5)->create(['user_id' => $fallbackCandidate->id, 'status' => 'published']);

        $response = $this->actingAs($authUser, 'api')
            ->getJson('/api/v1/followers/suggestions?limit=3');

        $response->assertOk();

        $suggestedUsers = $response->json('Suggested Users');

        $this->assertCount(3, $suggestedUsers);
        $this->assertSame($bestMatch->id, $suggestedUsers[0]['id']);
        $this->assertSame($secondMatch->id, $suggestedUsers[1]['id']);
        $this->assertSame($fallbackCandidate->id, $suggestedUsers[2]['id']);
    }

    public function test_it_excludes_self_and_already_followed_users_from_suggestions(): void
    {
        $authUser = User::factory()->create();
        $topic = Topic::create(['name' => 'PHP']);

        $authUser->topics()->attach($topic->id);

        $followedUser = User::factory()->create();
        $eligibleUser = User::factory()->create();

        $authUser->following()->attach($followedUser->id);

        $followedUser->topics()->attach($topic->id);
        $eligibleUser->topics()->attach($topic->id);

        Post::factory()->create(['user_id' => $followedUser->id, 'status' => 'published']);
        Post::factory()->create(['user_id' => $eligibleUser->id, 'status' => 'published']);

        $response = $this->actingAs($authUser, 'api')
            ->getJson('/api/v1/followers/suggestions?limit=5');

        $response->assertOk();

        $suggestedIds = collect($response->json('Suggested Users'))->pluck('id')->all();

        $this->assertNotContains($authUser->id, $suggestedIds);
        $this->assertNotContains($followedUser->id, $suggestedIds);
        $this->assertContains($eligibleUser->id, $suggestedIds);
    }
}

