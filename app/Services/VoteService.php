<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\AnswerVote;
use App\Models\Question;
use App\Models\QuestionVote;
use App\Models\User;

class VoteService
{
    /**
     * Toggle vote on a question.
     * - Same vote type → remove (toggle off)
     * - Different vote type → switch
     * - No existing vote → create
     */
    public function voteQuestion(Question $question, User $user, string $voteType): ?QuestionVote
    {
        $existing = $question->votes()->where('user_id', $user->id)->first();

        if ($existing) {
            if ($existing->vote_type === $voteType) {
                $existing->delete();
                return null;
            }
            $existing->update(['vote_type' => $voteType]);
            return $existing;
        }

        return $question->votes()->create([
            'user_id'   => $user->id,
            'vote_type' => $voteType,
        ]);
    }

    /**
     * Toggle vote on an answer.
     */
    public function voteAnswer(Answer $answer, User $user, string $voteType): ?AnswerVote
    {
        $existing = $answer->votes()->where('user_id', $user->id)->first();

        if ($existing) {
            if ($existing->vote_type === $voteType) {
                $existing->delete();
                return null;
            }
            $existing->update(['vote_type' => $voteType]);
            return $existing;
        }

        return $answer->votes()->create([
            'user_id'   => $user->id,
            'vote_type' => $voteType,
        ]);
    }

    public function removeQuestionVote(Question $question, User $user): bool
    {
        return $question->votes()->where('user_id', $user->id)->delete() > 0;
    }

    public function removeAnswerVote(Answer $answer, User $user): bool
    {
        return $answer->votes()->where('user_id', $user->id)->delete() > 0;
    }

    public function markAnswerHelpful(Answer $answer): Answer
    {
        $answer->increment('helpful_count');
        return $answer->fresh();
    }

    public function getQuestionVoteScore(Question $question): int
    {
        return $question->voteScore();
    }

    public function getAnswerVoteScore(Answer $answer): int
    {
        return $answer->voteScore();
    }
}
