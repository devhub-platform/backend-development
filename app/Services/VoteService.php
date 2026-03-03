<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\AnswerVote;
use App\Models\Question;
use App\Models\QuestionVote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VoteService
{
    /**
     * Toggle vote on a question - wrapped in transaction to prevent race conditions
     */
    public function voteQuestion(Question $question, User $user, string $voteType): ?QuestionVote
    {
        return DB::transaction(function () use ($question, $user, $voteType) {
            $existing = QuestionVote::where('question_id', $question->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->vote_type === $voteType) {
                    $existing->delete();
                    return null;
                }
                $existing->update(['vote_type' => $voteType]);
                return $existing;
            }

            return QuestionVote::create([
                'question_id' => $question->id,
                'user_id'     => $user->id,
                'vote_type'   => $voteType,
            ]);
        });
    }

    /**
     * Toggle vote on an answer - wrapped in transaction to prevent race conditions
     */
    public function voteAnswer(Answer $answer, User $user, string $voteType): ?AnswerVote
    {
        return DB::transaction(function () use ($answer, $user, $voteType) {
            $existing = AnswerVote::where('answer_id', $answer->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->vote_type === $voteType) {
                    $existing->delete();
                    return null;
                }
                $existing->update(['vote_type' => $voteType]);
                return $existing;
            }

            return AnswerVote::create([
                'answer_id' => $answer->id,
                'user_id'   => $user->id,
                'vote_type' => $voteType,
            ]);
        });
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
