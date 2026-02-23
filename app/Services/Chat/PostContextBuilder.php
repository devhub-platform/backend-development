<?php

namespace App\Services\Chat;

use App\Models\Post;
use App\Models\Comment;

class PostContextBuilder
{
    // Max chars for post content before truncation
    private const MAX_POST_CHARS     = 1500;
    // Max chars per comment
    private const MAX_COMMENT_CHARS  = 200;
    // Max number of top-level comments to include
    private const MAX_COMMENTS       = 5;

    /**
     * Build a token-efficient system prompt from the post and its top comments.
     */
    public function build(Post $post): string
    {
        $content  = $this->truncate($post->content ?? '', self::MAX_POST_CHARS);
        $comments = $this->buildCommentsBlock($post->id);

        return <<<PROMPT
You are an AI assistant embedded inside a developer blog post.
Always keep the following post as your background knowledge, even if the user asks general questions.
Lean on this context when relevant, but also answer general questions helpfully.

=== POST ===
Title: {$post->title}
Content:
{$content}

{$comments}
=== END OF CONTEXT ===

You can: summarize, explain, analyze comments, detect sentiment, or answer questions about this post.
Be concise and developer-friendly.
PROMPT;
    }

    private function buildCommentsBlock(int $postId): string
    {
        $comments = Comment::where('post_id', $postId)
            ->whereNull('parent_id')
            ->select(['id', 'content', 'user_id'])
            ->latest()
            ->limit(self::MAX_COMMENTS)
            ->get();

        if ($comments->isEmpty()) {
            return '';
        }

        $lines = ["=== TOP COMMENTS ==="];
        foreach ($comments as $i => $comment) {
            $text    = $this->truncate($comment->content, self::MAX_COMMENT_CHARS);
            $lines[] = ($i + 1) . ". {$text}";
        }
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function truncate(string $text, int $max): string
    {
        $text = strip_tags($text);
        if (strlen($text) <= $max) return $text;
        return substr($text, 0, $max) . '...';
    }
}
