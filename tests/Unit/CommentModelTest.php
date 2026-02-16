<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $comment = new Comment();

        $fillable = [
            'post_id',
            'user_id',
            'content',
            'parent_id',
            'is_pinned',
        ];

        $this->assertEquals($fillable, $comment->getFillable());
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($user->id, $comment->user->id);
    }

    /** @test */
    public function it_belongs_to_a_post()
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->assertInstanceOf(Post::class, $comment->post);
        $this->assertEquals($post->id, $comment->post->id);
    }

    /** @test */
    public function it_can_have_replies()
    {
        $parentComment = Comment::factory()->create();
        $reply1 = Comment::factory()->create(['parent_id' => $parentComment->id]);
        $reply2 = Comment::factory()->create(['parent_id' => $parentComment->id]);

        $this->assertCount(2, $parentComment->replies);
    }

    /** @test */
    public function it_can_have_a_parent()
    {
        $parentComment = Comment::factory()->create();
        $childComment = Comment::factory()->create(['parent_id' => $parentComment->id]);

        $this->assertInstanceOf(Comment::class, $childComment->parent);
        $this->assertEquals($parentComment->id, $childComment->parent->id);
    }

    /** @test */
    public function it_can_get_all_nested_replies()
    {
        $level1 = Comment::factory()->create();
        $level2 = Comment::factory()->create(['parent_id' => $level1->id]);
        $level3 = Comment::factory()->create(['parent_id' => $level2->id]);

        $allReplies = $level1->allReplies;

        $this->assertCount(1, $allReplies);
        $this->assertCount(1, $allReplies->first()->allReplies);
    }

    /** @test */
    public function it_can_scope_top_level_comments()
    {
        $topLevelComment = Comment::factory()->create(['parent_id' => null]);
        $replyComment = Comment::factory()->create(['parent_id' => $topLevelComment->id]);

        $topLevelComments = Comment::topLevel()->get();

        $this->assertCount(1, $topLevelComments);
        $this->assertEquals($topLevelComment->id, $topLevelComments->first()->id);
    }

    /** @test */
    public function it_can_scope_pinned_first()
    {
        $regularComment = Comment::factory()->create(['is_pinned' => false]);
        $pinnedComment = Comment::factory()->create(['is_pinned' => true]);

        $comments = Comment::pinnedFirst()->get();

        $this->assertEquals($pinnedComment->id, $comments->first()->id);
    }

    /** @test */
    public function it_can_check_if_comment_is_reply()
    {
        $parentComment = Comment::factory()->create(['parent_id' => null]);
        $replyComment = Comment::factory()->create(['parent_id' => $parentComment->id]);

        $this->assertFalse($parentComment->isReply());
        $this->assertTrue($replyComment->isReply());
    }

    /** @test */
    public function it_can_get_depth_of_comment()
    {
        $level0 = Comment::factory()->create(['parent_id' => null]);
        $level1 = Comment::factory()->create(['parent_id' => $level0->id]);
        $level2 = Comment::factory()->create(['parent_id' => $level1->id]);

        $this->assertEquals(0, $level0->getDepth());
        $this->assertEquals(1, $level1->getDepth());
        $this->assertEquals(2, $level2->getDepth());
    }

    /** @test */
    public function it_casts_is_pinned_to_boolean()
    {
        $comment = Comment::factory()->create(['is_pinned' => 1]);

        $this->assertIsBool($comment->is_pinned);
        $this->assertTrue($comment->is_pinned);
    }

    /** @test */
    public function it_uses_soft_deletes()
    {
        $comment = Comment::factory()->create();
        $commentId = $comment->id;

        $comment->delete();

        $this->assertSoftDeleted('comments', ['id' => $commentId]);

        $deletedComment = Comment::withTrashed()->find($commentId);
        $this->assertNotNull($deletedComment->deleted_at);
    }

    /** @test */
    public function it_prevents_creating_comment_without_post()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Comment::factory()->create(['post_id' => null]);
    }

    /** @test */
    public function it_has_reactable_trait()
    {
        $comment = Comment::factory()->create();

        // Check if the comment has the reactable trait methods
        $this->assertTrue(method_exists($comment, 'reactions'));
    }

    /** @test */
    public function nested_replies_maintain_hierarchy()
    {
        $level1 = Comment::factory()->create();
        $level2a = Comment::factory()->create(['parent_id' => $level1->id]);
        $level2b = Comment::factory()->create(['parent_id' => $level1->id]);
        $level3 = Comment::factory()->create(['parent_id' => $level2a->id]);

        // Level 1 should have 2 direct replies
        $this->assertCount(2, $level1->replies);

        // Level 2a should have 1 reply
        $this->assertCount(1, $level2a->replies);

        // Level 2b should have no replies
        $this->assertCount(0, $level2b->replies);
    }

    /** @test */
    public function it_can_be_pinned_and_unpinned()
    {
        $comment = Comment::factory()->create(['is_pinned' => false]);

        $this->assertFalse($comment->is_pinned);

        $comment->update(['is_pinned' => true]);
        $this->assertTrue($comment->fresh()->is_pinned);

        $comment->update(['is_pinned' => false]);
        $this->assertFalse($comment->fresh()->is_pinned);
    }
}

