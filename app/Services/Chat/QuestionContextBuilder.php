<?php

namespace App\Services\Chat;

use App\Models\Question;
use App\Models\Answer;

class QuestionContextBuilder
{
    // Token budget: 70% for context, 30% for response
    private const MAX_CONTEXT_TOKENS  = 2800; // ~70% of 4000
    private const MAX_QUESTION_CHARS  = 1000;
    private const MAX_ANSWER_CHARS    = 600;
    private const MAX_ANSWERS         = 5;

    /**
     * Build token-efficient system prompt from question + answers.
     * Priority: Question → Accepted Answer → Top Voted → Rest
     */
    public function build(Question $question): string
    {
        $questionBlock  = $this->buildQuestionBlock($question);
        $acceptedBlock  = $this->buildAcceptedAnswerBlock($question);
        $topBlock       = $this->buildTopAnswersBlock($question, $question->accepted_answer_id);

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

    private function buildAcceptedAnswerBlock(Question $question): string
    {
        if (!$question->acceptedAnswer) return '';

        $content = $this->truncate($question->acceptedAnswer->content, self::MAX_ANSWER_CHARS);
        $score   = $question->acceptedAnswer->voteScore();

        return <<<BLOCK

=== ACCEPTED ANSWER [Score: {$score}] ===
{$content}
BLOCK;
    }

    private function buildTopAnswersBlock(Question $question, ?int $excludeId): string
    {
        $answers = Answer::where('question_id', $question->id)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->select(['id', 'content', 'is_accepted', 'helpful_count'])
            ->orderByDesc('helpful_count')
            ->limit(self::MAX_ANSWERS)
            ->get();

        if ($answers->isEmpty()) return '';

        $lines = ["\n=== OTHER ANSWERS ==="];
        foreach ($answers as $i => $answer) {
            $content  = $this->truncate($answer->content, self::MAX_ANSWER_CHARS);
            $score    = $answer->voteScore();
            $lines[]  = "\nAnswer #" . ($i + 1) . " [Score: {$score}]:\n{$content}";
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
