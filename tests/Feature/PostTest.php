<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_all_published_posts()
    {
        Post::factory()->count(5)->create(['status' => 'published']);
        Post::factory()->count(2)->create(['status' => 'draft']);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_can_create_a_post()
    {
        $postData = [
            'title' => 'Test Post Title',
            'content' => 'This is test content for the post.',
            'status' => 'published',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'content',
                    'status',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post Title',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_show_a_single_post()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published'
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $post->id,
                    'title' => $post->title,
                ]
            ]);
    }

    /** @test */
    public function it_can_update_own_post()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $updatedData = [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/posts/{$post->id}", $updatedData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function it_cannot_update_another_users_post()
    {
        $anotherUser = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        $updatedData = [
            'title' => 'Trying to Update',
            'content' => 'Should not work',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/posts/{$post->id}", $updatedData);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_delete_own_post()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        $this->assertSoftDeleted('posts', [
            'id' => $post->id,
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_create_post()
    {
        $postData = [
            'title' => 'Test Post',
            'content' => 'Test content',
        ];

        $response = $this->postJson('/api/v1/posts', $postData);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_post_creation_data()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/posts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    /** @test */
    public function it_can_get_user_posts()
    {
        Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'published'
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/user/posts');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_get_top_posts_by_views()
    {
        Post::factory()->create(['views' => 100, 'status' => 'published']);
        Post::factory()->create(['views' => 50, 'status' => 'published']);
        Post::factory()->create(['views' => 200, 'status' => 'published']);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/posts/top-views');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'views_count',
            ]);

        $data = $response->json('data');
        $this->assertEquals(200, $data[0]['views']);
    }

    /** @test */
    public function it_can_get_draft_posts()
    {
        Post::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);
        Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'published'
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/posts/drafts');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_excludes_draft_posts_from_public_listing()
    {
        Post::factory()->count(3)->create(['status' => 'published']);
        Post::factory()->count(2)->create(['status' => 'draft']);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_can_attach_tags_to_post()
    {
        $tag1 = Tag::factory()->create(['name' => 'Laravel']);
        $tag2 = Tag::factory()->create(['name' => 'PHP']);

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $post->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertCount(2, $post->tags);
        $this->assertTrue($post->tags->contains('name', 'Laravel'));
    }

    /** @test */
    public function it_generates_slug_from_title()
    {
        $postData = [
            'title' => 'This is a Test Title',
            'content' => 'Test content',
            'status' => 'published',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/posts', $postData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('posts', [
            'slug' => 'this-is-a-test-title',
        ]);
    }

    /** @test */
    public function it_can_restore_soft_deleted_post()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $post->delete();

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/posts/{$post->id}/restore");

        $response->assertStatus(200);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function post_belongs_to_user()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($this->user->id, $post->user->id);
    }

    /** @test */
    public function post_has_many_comments()
    {
        $post = Post::factory()
            ->hasComments(3)
            ->create();

        $this->assertCount(3, $post->comments);
    }
}
