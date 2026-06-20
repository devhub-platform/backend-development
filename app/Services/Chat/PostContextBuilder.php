<?php

namespace App\Services\Chat;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class PostContextBuilder
{
    private const MAX_POST_CHARS    = 1500;
    private const MAX_COMMENT_CHARS = 200;
    private const MAX_COMMENTS      = 5;
    private const CACHE_TTL         = 60 * 10; // 10 minutes

    public function build(Post $post): string
    {
        return Cache::remember(
            "post:context:{$post->id}",
            self::CACHE_TTL,
            fn() => $this->buildPrompt($post)
        );
    }

    private function buildPrompt(Post $post): string
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

        if ($comments->isEmpty()) return '';

        $lines = ["=== TOP COMMENTS ==="];
        foreach ($comments as $i => $comment) {
            $lines[] = ($i + 1) . ". " . $this->truncate($comment->content, self::MAX_COMMENT_CHARS);
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
