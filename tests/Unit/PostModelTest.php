<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $post = new Post();

        $fillable = [
            'user_id',
            'title',
            'id',
            'slug',
            'image_url',
            'content',
            'status',
            'read_time',
            'cover_image',
            'views',
            'is_edit'
        ];

        $this->assertEquals($fillable, $post->getFillable());
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($user->id, $post->user->id);
    }

    /** @test */
    public function it_has_many_comments()
    {
        $post = Post::factory()->create();
        Comment::factory()->count(3)->create(['post_id' => $post->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $post->comments);
        $this->assertCount(3, $post->comments);
    }

    /** @test */
    public function it_can_have_tags()
    {
        $post = Post::factory()->create();
        $tags = \App\Models\Tag::factory()->count(2)->create();

        $post->tags()->attach($tags->pluck('id'));

        $this->assertCount(2, $post->tags);
    }

    /** @test */
    public function it_has_post_views_relationship()
    {
        $post = Post::factory()->create();

        $user = User::factory()->create();
        $post->postViews()->create([
            'user_id' => $user->id,
            'viewed_at' => now(),
        ]);

        $this->assertCount(1, $post->postViews);
    }

    /** @test */
    public function it_calculates_unique_viewers_count()
    {
        $post = Post::factory()->create();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $post->postViews()->create([
            'user_id' => $user1->id,
            'viewed_at' => now(),
        ]);

        $post->postViews()->create([
            'user_id' => $user2->id,
            'viewed_at' => now(),
        ]);

        // The unique_viewers_count attribute counts distinct user_ids
        $this->assertEquals(2, $post->unique_viewers_count);
    }

    /** @test */
    public function it_can_be_saved_by_users()
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        $post->savedBy()->attach($user->id);

        $this->assertTrue($post->savedBy->contains($user->id));
    }

    /** @test */
    public function it_can_be_viewed_by_users()
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        $post->viewedByUsers()->attach($user->id, [
            'viewed_at' => now(),
        ]);

        $this->assertTrue($post->viewedByUsers->contains($user->id));
    }

    /** @test */
    public function it_has_searchable_array()
    {
        $post = Post::factory()->create([
            'title' => 'Test Title',
            'content' => 'Test Content',
        ]);

        $searchableArray = $post->toSearchableArray();

        $this->assertArrayHasKey('id', $searchableArray);
        $this->assertArrayHasKey('title', $searchableArray);
        $this->assertArrayHasKey('content', $searchableArray);
        $this->assertEquals('Test Title', $searchableArray['title']);
    }

    /** @test */
    public function it_casts_views_to_integer()
    {
        $post = Post::factory()->create(['views' => '100']);

        $this->assertIsInt($post->views);
        $this->assertEquals(100, $post->views);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $post = Post::factory()->create();
        $postId = $post->id;

        $post->delete();

        $this->assertSoftDeleted('posts', ['id' => $postId]);

        $deletedPost = Post::withTrashed()->find($postId);
        $this->assertNotNull($deletedPost->deleted_at);
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete()
    {
        $post = Post::factory()->create();
        $postId = $post->id;

        $post->delete();
        $this->assertSoftDeleted('posts', ['id' => $postId]);

        Post::withTrashed()->find($postId)->restore();

        $restoredPost = Post::find($postId);
        $this->assertNotNull($restoredPost);
        $this->assertNull($restoredPost->deleted_at);
    }
}

