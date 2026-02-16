<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingListTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_create_a_reading_list()
    {
        $readingListData = [
            'title' => 'My Favorite Articles',
            'description' => 'A collection of my favorite tech articles',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/reading-lists', $readingListData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'title',
                ]
            ]);

        $this->assertDatabaseHas('reading_lists', [
            'title' => 'My Favorite Articles',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_list_user_reading_lists()
    {
        ReadingList::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/reading-lists/lists/posts');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'count', 'data']);
    }

    /** @test */
    public function it_can_show_a_reading_list()
    {
        $readingList = ReadingList::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/reading-lists/{$readingList->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'title']
            ]);
    }

    /** @test */
    public function it_can_update_a_reading_list()
    {
        $readingList = ReadingList::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/reading-lists/{$readingList->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reading_lists', [
            'id' => $readingList->id,
            'title' => 'Updated Title',
        ]);
    }

    /** @test */
    public function it_can_delete_a_reading_list()
    {
        $readingList = ReadingList::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/reading-lists/{$readingList->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseMissing('reading_lists', [
            'id' => $readingList->id,
        ]);
    }

    /** @test */
    public function it_can_add_post_to_reading_list()
    {
        $readingList = ReadingList::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/reading-lists/{$readingList->id}/add-post/{$post->id}");

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('reading_list_story', [
            'reading_list_id' => $readingList->id,
            'post_id' => $post->id,
        ]);
    }

    /** @test */
    public function it_can_remove_post_from_reading_list()
    {
        $readingList = ReadingList::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $post = Post::factory()->create(['status' => 'published']);
        $readingList->posts()->attach($post->id);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/reading-lists/{$readingList->id}/remove-post/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseMissing('reading_list_story', [
            'reading_list_id' => $readingList->id,
            'post_id' => $post->id,
        ]);
    }

    /** @test */
    public function it_can_get_posts_in_reading_list()
    {
        $readingList = ReadingList::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $posts = Post::factory()->count(3)->create(['status' => 'published']);
        foreach ($posts as $post) {
            $readingList->posts()->attach($post->id);
        }

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/reading-lists/{$readingList->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'data']);
    }

    /** @test */
    public function it_cannot_update_another_users_reading_list()
    {
        $anotherUser = User::factory()->create();
        $readingList = ReadingList::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        $updateData = [
            'title' => 'Trying to Update',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/reading-lists/{$readingList->id}", $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_cannot_delete_another_users_reading_list()
    {
        $anotherUser = User::factory()->create();
        $readingList = ReadingList::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/reading-lists/{$readingList->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_validates_reading_list_creation_data()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/reading-lists', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function it_requires_authentication_to_create_reading_list()
    {
        $readingListData = [
            'title' => 'Test List',
        ];

        $response = $this->postJson('/api/v1/reading-lists', $readingListData);

        $response->assertStatus(401);
    }

    /** @test */
    public function reading_list_belongs_to_user()
    {
        $readingList = ReadingList::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $readingList->user);
        $this->assertEquals($this->user->id, $readingList->user->id);
    }

    /** @test */
    public function reading_list_can_have_many_posts()
    {
        $readingList = ReadingList::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $posts = Post::factory()->count(5)->create(['status' => 'published']);
        foreach ($posts as $post) {
            $readingList->posts()->attach($post->id);
        }

        $this->assertCount(5, $readingList->posts);
    }

    /** @test */
    public function it_can_check_if_post_is_in_reading_list()
    {
        $readingList = ReadingList::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $post = Post::factory()->create(['status' => 'published']);
        $readingList->posts()->attach($post->id);

        $this->assertTrue($readingList->posts->contains($post->id));
    }
}

