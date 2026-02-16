<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published'
        ]);
    }

    /** @test */
    public function it_can_create_a_comment_on_a_post()
    {
        $commentData = [
            'content' => 'This is a test comment',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/posts/{$this->post->id}/comments", $commentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'comment' => [
                    'id',
                    'content',
                    'post_id',
//                    'user_id',
                ]
            ]);

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a test comment',
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_cannot_create_comment_without_post_association()
    {
        $commentData = [
            'content' => 'This is a test comment',
        ];

        // Try to create comment without valid post
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/posts/99999/comments", $commentData);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_requires_content_to_create_comment()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/posts/{$this->post->id}/comments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function it_requires_authentication_to_create_comment()
    {
        $commentData = [
            'content' => 'This is a test comment',
        ];

        $response = $this->postJson("/api/v1/posts/{$this->post->id}/comments", $commentData);

        $response->assertStatus(401);
    }

    /** @test */
    /* Comment index route not enabled - apiResource is commented out
    public function it_can_list_all_comments()
    {
        Comment::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/comments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'comments',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ]
            ]);
    }
    */

    /** @test */
    /* Comment show route not enabled - apiResource is commented out
    public function it_can_show_a_single_comment()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'comment' => [
                    'id',
                    'content',
                    'post_id',
                    'user_id',
                ]
            ]);
    }
    */

    /** @test */
    /* Comment update route not enabled - apiResource is commented out
    public function it_can_update_own_comment()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'Original content',
        ]);

        $updatedData = [
            'content' => 'Updated content',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/comments/{$comment->id}", $updatedData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated content',
        ]);
    }
    */

    /** @test */
    /* Comment update route not enabled - apiResource is commented out
    public function it_cannot_update_another_users_comment()
    {
        $anotherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $anotherUser->id,
        ]);

        $updatedData = [
            'content' => 'Trying to update',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/comments/{$comment->id}", $updatedData);

        $response->assertStatus(403);
    }
    */

    /** @test */
    /* Comment destroy route not enabled - apiResource is commented out
    public function it_can_delete_own_comment()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('comments', [
            'id' => $comment->id,
        ]);
    }
    */

    /** @test */
//    public function it_can_create_nested_reply()
//    {
//        $parentComment = Comment::factory()->create([
//            'post_id' => $this->post->id,
//            'user_id' => $this->user->id,
//        ]);
//
//        $replyData = [
//            'content' => 'This is a reply',
//            'parent_id' => $parentComment->id,
//        ];
//
//        $response = $this->actingAs($this->user, 'api')
//            ->postJson("/api/v1/posts/{$this->post->id}/comments", $replyData);
//
//        $response->assertStatus(201);
//
//        $this->assertDatabaseHas('comments', [
//            'content' => 'This is a reply',
//            'parent_id' => $parentComment->id,
//        ]);
//    }

    /** @test */
    public function it_can_get_comment_replies()
    {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        Comment::factory()->count(3)->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $parentComment->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/comments/{$parentComment->id}/replies");

        $response->assertStatus(200);

        $comment = Comment::find($parentComment->id);
        $this->assertCount(3, $comment->replies);
    }

    /** @test */
    public function it_can_pin_a_comment()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/comments/{$comment->id}/pin");

        $response->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_pinned' => true,
        ]);
    }

    /** @test */
    public function it_can_unpin_a_comment()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'is_pinned' => true,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/comments/{$comment->id}/unpin");

        $response->assertStatus(200);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_pinned' => false,
        ]);
    }

    /** @test */
    public function comment_belongs_to_post()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
        ]);

        $this->assertInstanceOf(Post::class, $comment->post);
        $this->assertEquals($this->post->id, $comment->post->id);
    }

    /** @test */
    public function comment_belongs_to_user()
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($this->user->id, $comment->user->id);
    }

    /** @test */
    public function it_checks_if_comment_is_reply()
    {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
        ]);

        $replyComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
        ]);

        $this->assertFalse($parentComment->isReply());
        $this->assertTrue($replyComment->isReply());
    }

    /** @test */
    public function it_can_get_comment_depth()
    {
        $level1 = Comment::factory()->create([
            'post_id' => $this->post->id,
        ]);

        $level2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $level1->id,
        ]);

        $level3 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $level2->id,
        ]);

        $this->assertEquals(0, $level1->getDepth());
        $this->assertEquals(1, $level2->getDepth());
        $this->assertEquals(2, $level3->getDepth());
    }

    /** @test */
    public function it_orders_pinned_comments_first()
    {
        $regularComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'is_pinned' => false,
            'created_at' => now()->subDay(),
        ]);

        $pinnedComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'is_pinned' => true,
            'created_at' => now()->subDays(2),
        ]);

        $comments = Comment::pinnedFirst()->get();

        $this->assertEquals($pinnedComment->id, $comments->first()->id);
    }
}

