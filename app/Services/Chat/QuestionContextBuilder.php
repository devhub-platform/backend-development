<?php

namespace App\Services\Chat;

use App\Models\Answer;
use App\Models\Question;

class QuestionContextBuilder
{
    // Token budget: 70% for context, 30% for response
    private const MAX_QUESTION_CHARS = 1000;
    private const MAX_ANSWER_CHARS   = 600;
    private const MAX_ANSWERS        = 5;

    /**
     * Build token-efficient system prompt.
     * Priority: Question → Accepted Answers → Top Voted → Rest
     */
    public function build(Question $question): string
    {
        $questionBlock  = $this->buildQuestionBlock($question);
        $acceptedBlock  = $this->buildAcceptedAnswersBlock($question);
        $topBlock       = $this->buildTopAnswersBlock($question);

        $context = implode("\n", array_filter([
            $questionBlock,
            $acceptedBlock,
            $topBlock,
        ]));

        return <<<PROMPT
You are an AI assistant embedded inside a developer Q&A platform (similar to StackOverflow).
Always keep the following question and answers as your background knowledge.
Even if the user asks something general, keep this discussion as context.

You can help with:
- Summarizing the discussion
- Comparing answers and recommending the best solution
- Explaining the question or clarifying confusing parts
- Detecting outdated, insecure, or performance-inefficient answers
- Suggesting an improved combined solution

You must NOT:
- Modify accepted answers or votes
- Change any DB state
- Take authority actions
- Pretend to be the question author

{$context}

=== END OF CONTEXT ===
Be concise, developer-friendly, and technically accurate.
PROMPT;
    }

    private function buildQuestionBlock(Question $question): string
    {
        $content = $this->truncate($question->content ?? '', self::MAX_QUESTION_CHARS);
        $status  = $question->is_resolved ? 'Resolved ✓' : 'Unresolved';

        return <<<BLOCK
=== QUESTION [{$status}] ===
Title: {$question->title}
Content:
{$content}
BLOCK;
    }

    /**
     * Build accepted answers block - supports multiple accepted answers
     */
    private function buildAcceptedAnswersBlock(Question $question): string
    {
        $accepted = $question->answers
            ->where('is_accepted', true)
            ->values();

        if ($accepted->isEmpty()) return '';

        $lines = ["\n=== ACCEPTED ANSWER(S) ==="];
        foreach ($accepted as $i => $answer) {
            $content  = $this->truncate($answer->content, self::MAX_ANSWER_CHARS);
            $score    = $answer->voteScore();
            $lines[]  = "\nAccepted #" . ($i + 1) . " [Score: {$score}]:\n{$content}";
        }

        return implode("\n", $lines);
    }

    /**
     * Build top voted non-accepted answers block
     */
    private function buildTopAnswersBlock(Question $question): string
    {
        $answers = $question->answers
            ->where('is_accepted', false)
            ->sortByDesc(fn($a) => $a->voteScore())
            ->take(self::MAX_ANSWERS)
            ->values();

        if ($answers->isEmpty()) return '';

        $lines = ["\n=== OTHER ANSWERS ==="];
        foreach ($answers as $i => $answer) {
            $content = $this->truncate($answer->content, self::MAX_ANSWER_CHARS);
            $score   = $answer->voteScore();
            $lines[] = "\nAnswer #" . ($i + 1) . " [Score: {$score}]:\n{$content}";
        }

        return implode("\n", $lines);
    }

    private function truncate(string $text, int $max): string
    {
        $text = strip_tags($text);
        if (strlen($text) <= $max) return $text;
        return substr($text, 0, $max) . '...';
    }
}
