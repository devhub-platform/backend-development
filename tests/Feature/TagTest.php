<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
//    public function it_can_list_all_tags()
//    {
//        Tag::factory()->count(5)->create();
//
//        $response = $this->actingAs($this->user, 'api')
//            ->getJson('/api/v1/tags');
//
//        $response->assertStatus(200)
//            ->assertJsonStructure([
//                'data',
//            ]);
//    }

    /** @test */
//    public function it_can_create_a_tag()
//    {
//        $tagData = [
//            'name' => 'Laravel',
//            'description' => 'Laravel PHP Framework',
//        ];
//
//        $response = $this->actingAs($this->user, 'api')
//            ->postJson('/api/v1/tags', $tagData);
//
//        $response->assertStatus(201)
//            ->assertJsonStructure([
//                'data' => [
//                    'id',
//                    'name',
//                    'description',
//                ]
//            ]);
//
//        $this->assertDatabaseHas('tags', [
//            'name' => 'Laravel',
//        ]);
//    }
//
//    /** @test */
//    /* Tag show endpoint not implemented
//    public function it_can_show_a_single_tag()
//    {
//        $tag = Tag::factory()->create();
//
//        $response = $this->actingAs($this->user, 'api')
//            ->getJson("/api/v1/tags/{$tag->id}");
//
//        $response->assertStatus(200)
//            ->assertJson([
//                'data' => [
//                    'id' => $tag->id,
//                    'name' => $tag->name,
//                ]
//            ]);
//    }
//    */
//
//    /** @test */
//    public function it_can_get_posts_by_tag()
//    {
//        $tag = Tag::factory()->create(['name' => 'Laravel']);
//
//        $posts = Post::factory()->count(3)->create(['status' => 'published']);
//        foreach ($posts as $post) {
//            $post->tags()->attach($tag->id);
//        }
//
//        $response = $this->actingAs($this->user, 'api')
//            ->getJson("/api/v1/tags/{$tag->id}/posts");
//
//        $response->assertStatus(200)
//            ->assertJsonCount(3, 'data');
//    }
//
//    /** @test */
//    public function it_can_follow_a_tag()
//    {
//        $tag = Tag::factory()->create();
//
//        $response = $this->actingAs($this->user, 'api')
//            ->postJson("/api/v1/tags/{$tag->id}/follow");
//
//        $response->assertStatus(200);
//
//        $this->assertDatabaseHas('tag_follows', [
//            'user_id' => $this->user->id,
//            'tag_id' => $tag->id,
//        ]);
//    }
//
//    /** @test */
//    public function it_can_unfollow_a_tag()
//    {
//        $tag = Tag::factory()->create();
//
//        // First follow
//        $this->user->followedTags()->attach($tag->id);
//
//        $response = $this->actingAs($this->user, 'api')
//            ->deleteJson("/api/v1/tags/{$tag->id}/unfollow");
//
//        $response->assertStatus(200);
//
//        $this->assertDatabaseMissing('tag_follows', [
//            'user_id' => $this->user->id,
//            'tag_id' => $tag->id,
//        ]);
//    }
//
//    /** @test */
//    /* Get followed tags endpoint not implemented
//    public function it_can_get_followed_tags()
//    {
//        $tags = Tag::factory()->count(3)->create();
//
//        foreach ($tags as $tag) {
//            $this->user->followedTags()->attach($tag->id);
//        }
//
//        $response = $this->actingAs($this->user, 'api')
//            ->getJson('/api/v1/tags/following');
//
//        $response->assertStatus(200)
//            ->assertJsonCount(3, 'data');
//    }
//    */
//
//    /** @test */
//    /* Get trending tags endpoint not implemented
//    public function it_can_get_trending_tags()
//    {
//        $tag1 = Tag::factory()->create();
//        $tag2 = Tag::factory()->create();
//
//        // Create more posts with tag1
//        $posts1 = Post::factory()->count(5)->create(['status' => 'published']);
//        foreach ($posts1 as $post) {
//            $post->tags()->attach($tag1->id);
//        }
//
//        $posts2 = Post::factory()->count(2)->create(['status' => 'published']);
//        foreach ($posts2 as $post) {
//            $post->tags()->attach($tag2->id);
//        }
//
//        $response = $this->actingAs($this->user, 'api')
//            ->getJson('/api/v1/tags/trending');
//
//        $response->assertStatus(200)
//            ->assertJsonStructure([
//                'data',
//            ]);
//    }
//    */
//
//    /** @test */
//    public function it_requires_authentication_to_create_tag()
//    {
//        $tagData = [
//            'name' => 'Laravel',
//        ];
//
//        $response = $this->postJson('/api/v1/tags', $tagData);
//
//        $response->assertStatus(401);
//    }
//
//    /** @test */
//    public function it_validates_tag_creation_data()
//    {
//        $response = $this->actingAs($this->user, 'api')
//            ->postJson('/api/v1/tags', []);
//
//        $response->assertStatus(422)
//            ->assertJsonValidationErrors(['name']);
//    }
//
//    /** @test */
//    public function it_requires_unique_tag_name()
//    {
//        Tag::factory()->create(['name' => 'Laravel']);
//
//        $tagData = [
//            'name' => 'Laravel',
//        ];
//
//        $response = $this->actingAs($this->user, 'api')
//            ->postJson('/api/v1/tags', $tagData);
//
//        $response->assertStatus(422)
//            ->assertJsonValidationErrors(['name']);
//    }
//
//    /** @test */
//    /* Tag update functionality not implemented yet
//    public function it_can_update_a_tag()
//    {
//        $tag = Tag::factory()->create(['name' => 'Old Name']);
//
//        $updateData = [
//            'name' => 'New Name',
//            'description' => 'Updated description',
//        ];
//
//        $response = $this->actingAs($this->user, 'api')
//            ->putJson("/api/v1/tags/{$tag->id}", $updateData);
//
//        $response->assertStatus(200);
//
//        $this->assertDatabaseHas('tags', [
//            'id' => $tag->id,
//            'name' => 'New Name',
//        ]);
//    }
//    */
//
//    /** @test */
//    /* Tag deletion functionality not implemented yet
//    public function it_can_delete_a_tag()
//    {
//        $tag = Tag::factory()->create();
//
//        $response = $this->actingAs($this->user, 'api')
//            ->deleteJson("/api/v1/tags/{$tag->id}");
//
//        $response->assertStatus(200)
//            ->assertJsonStructure(['message']);
//
//        $this->assertDatabaseMissing('tags', [
//            'id' => $tag->id,
//        ]);
//    }
//    */
//
//    /** @test */
//    public function tag_can_have_many_posts()
//    {
//        $tag = Tag::factory()->create();
//        $posts = Post::factory()->count(3)->create();
//
//        foreach ($posts as $post) {
//            $post->tags()->attach($tag->id);
//        }
//
//        $this->assertCount(3, $tag->posts);
//    }
//
//    /** @test */
//    public function tag_can_be_followed_by_users()
//    {
//        $tag = Tag::factory()->create();
//        $users = User::factory()->count(3)->create();
//
//        foreach ($users as $user) {
//            $user->followedTags()->attach($tag->id);
//        }
//
//        $this->assertCount(3, $tag->followers);
//    }
}

