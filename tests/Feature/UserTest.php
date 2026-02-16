<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
//    public function it_can_list_all_users()
//    {
//        User::factory()->count(5)->create();
//
//        $response = $this->actingAs($this->user, 'api')
//            ->getJson('/api/v1/users');
//
//        $response->assertStatus(200)
//            ->assertJsonStructure([
//                'data',
//            ]);
//    }

    /** @test */
    public function it_can_show_user_profile()
    {
        $targetUser = User::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $targetUser->id,
                    'username' => $targetUser->username,
                ]
            ]);
    }

    /** @test */
    public function it_can_get_user_posts()
    {
        $targetUser = User::factory()->create();
        Post::factory()->count(3)->create([
            'user_id' => $targetUser->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/users/{$targetUser->id}/posts");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
//    public function it_can_update_own_profile()
//    {
//        $updateData = [
//            'name' => 'Updated Name',
//            'bio' => 'Updated bio',
//            'location' => 'New York',
//        ];
//
//        $response = $this->actingAs($this->user, 'api')
//            ->putJson('/api/v1/profile', $updateData);
//
//        $response->assertStatus(200);
//
//        $this->assertDatabaseHas('users', [
//            'id' => $this->user->id,
//            'name' => 'Updated Name',
//            'bio' => 'Updated bio',
//        ]);
//    }

    /** @test */
//    public function it_can_follow_another_user()
//    {
//        $targetUser = User::factory()->create();
//
//        $response = $this->actingAs($this->user, 'api')
//            ->postJson("/api/v1/users/{$targetUser->id}/follow");
//
//        $response->assertStatus(200);
//
//        $this->assertDatabaseHas('follows', [
//            'follower_id' => $this->user->id,
//            'following_id' => $targetUser->id,
//        ]);
//    }

    /** @test */
//    public function it_can_unfollow_a_user()
//    {
//        $targetUser = User::factory()->create();
//
//        // First follow
//        $this->user->following()->attach($targetUser->id);
//
//        $response = $this->actingAs($this->user, 'api')
//            ->postJson("/api/v1/users/{$targetUser->id}/unfollow");
//
//        $response->assertStatus(200);
//
//        $this->assertDatabaseMissing('follows', [
//            'follower_id' => $this->user->id,
//            'following_id' => $targetUser->id,
//        ]);
//    }

    /** @test */
//    public function it_cannot_follow_self()
//    {
//        $response = $this->actingAs($this->user, 'api')
//            ->postJson("/api/v1/users/{$this->user->id}/follow");
//
//        $response->assertStatus(422);
//    }
//
//    /** @test */
//    public function it_can_get_user_followers()
//    {
//        $follower1 = User::factory()->create();
//        $follower2 = User::factory()->create();
//
//        $this->user->followers()->attach([$follower1->id, $follower2->id]);
//
//        $response = $this->actingAs($this->user, 'api')
//            ->getJson("/api/v1/users/{$this->user->id}/followers");
//
//        $response->assertStatus(200)
//            ->assertJsonCount(2, 'data');
//    }

    /** @test */
//    public function it_can_get_user_following()
//    {
//        $following1 = User::factory()->create();
//        $following2 = User::factory()->create();
//
//        $this->user->following()->attach([$following1->id, $following2->id]);
//
//        $response = $this->actingAs($this->user, 'api')
//            ->getJson("/api/v1/users/{$this->user->id}/following");
//
//        $response->assertStatus(200)
//            ->assertJsonCount(2, 'data');
//    }

    /** @test */
    public function it_can_get_recommended_users()
    {
        User::factory()->count(10)->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/users/recommended');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /** @test */
    public function it_can_search_users()
    {
        User::factory()->create(['username' => 'john_doe']);
        User::factory()->create(['username' => 'jane_smith']);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/search/users?username=john');

        $response->assertStatus(200);
    }

    /** @test */
//    public function it_can_block_a_user()
//    {
//        $targetUser = User::factory()->create();
//
//        $response = $this->actingAs($this->user, 'api')
//            ->postJson("/api/v1/reports/block/{$targetUser->id}");
//
//        $response->assertStatus(200);
//
//        $this->assertDatabaseHas('blocked_users', [
//            'user_id' => $this->user->id,
//            'blocked_user_id' => $targetUser->id,
//        ]);
//    }
//
//    /** @test */
//    public function it_can_unblock_a_user()
//    {
//        $targetUser = User::factory()->create();
//
//        // First block
//        $this->user->blockedUsers()->attach($targetUser->id);
//
//        $response = $this->actingAs($this->user, 'api')
//            ->postJson("/api/v1/reports/unblock/{$targetUser->id}");
//
//        $response->assertStatus(200);
//
//        $this->assertDatabaseMissing('blocked_users', [
//            'user_id' => $this->user->id,
//            'blocked_user_id' => $targetUser->id,
//        ]);
//    }

    /** @test */
    public function blocked_user_posts_are_hidden()
    {
        $blockedUser = User::factory()->create();

        Post::factory()->count(2)->create([
            'user_id' => $blockedUser->id,
            'status' => 'published',
        ]);

        Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'published',
        ]);

        // Block the user
        $this->user->blockedUsers()->attach($blockedUser->id);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
//    public function it_can_update_user_skills()
//    {
//        $updateData = [
//            'skills' => 'PHP, Laravel, JavaScript, React',
//        ];
//
//        $response = $this->actingAs($this->user, 'api')
//            ->putJson('/api/v1/profile', $updateData);
//
//        $response->assertStatus(200);
//
//        $this->assertDatabaseHas('users', [
//            'id' => $this->user->id,
//            'skills' => 'PHP, Laravel, JavaScript, React',
//        ]);
//    }

    /** @test */
    public function it_can_get_users_with_similar_skills()
    {
        $this->user->update(['skills' => 'PHP, Laravel, JavaScript']);

        $similarUser = User::factory()->create(['skills' => 'PHP, Laravel, Vue']);
        $differentUser = User::factory()->create(['skills' => 'Python, Django']);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/users/{$this->user->id}/similar-skills");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /** @test */
    /* User account deletion functionality not implemented yet
    public function it_can_soft_delete_user_account()
    {
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        $this->assertSoftDeleted('users', [
            'id' => $this->user->id,
        ]);
    }
    */

//
//    {
//        Post::factory()->count(5)->create([
//            'user_id' => $this->user->id,
//        ]);
//
//        $this->assertCount(5, $this->user->posts);
//    }

    /** @test */
    public function user_can_have_followers_and_following()
    {
        $follower = User::factory()->create();
        $following = User::factory()->create();

        $this->user->followers()->attach($follower->id);
        $this->user->following()->attach($following->id);

        $this->assertCount(1, $this->user->followers);
        $this->assertCount(1, $this->user->following);
    }
}

