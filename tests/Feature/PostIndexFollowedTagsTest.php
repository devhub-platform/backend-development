<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostIndexFollowedTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_prioritizes_posts_with_followed_tags(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();

        $followedTag = Tag::factory()->create(['name' => 'laravel']);
        $otherTag = Tag::factory()->create(['name' => 'golang']);

        $viewer->followedTags()->attach($followedTag->id);

        $matchedPost = Post::factory()->create([
            'user_id' => $author->id,
            'status' => 'published',
            'created_at' => now()->subHours(2),
        ]);
        $matchedPost->tags()->attach($followedTag->id);

        $unmatchedPost = Post::factory()->create([
            'user_id' => $author->id,
            'status' => 'published',
            'created_at' => now()->subMinutes(5),
        ]);
        $unmatchedPost->tags()->attach($otherTag->id);

        $response = $this->actingAs($viewer, 'api')->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $matchedPost->id);
    }

    public function test_index_keeps_latest_order_when_user_follows_no_tags(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $tag = Tag::factory()->create(['name' => 'php']);

        $olderPost = Post::factory()->create([
            'user_id' => $author->id,
            'status' => 'published',
            'created_at' => now()->subHours(3),
        ]);
        $olderPost->tags()->attach($tag->id);

        $newerPost = Post::factory()->create([
            'user_id' => $author->id,
            'status' => 'published',
            'created_at' => now()->subMinutes(2),
        ]);
        $newerPost->tags()->attach($tag->id);

        $response = $this->actingAs($viewer, 'api')->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $newerPost->id);
    }
}

