<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\AnswerVote;
use App\Models\Question;
use App\Models\QuestionVote;
use App\Models\User;

class VoteService
{

    public function voteQuestion(Question $question, User $user, string $voteType): ?QuestionVote
    {
        $existingVote = $question->votes()->where('user_id', $user->id)->first();

        if ($existingVote) {
            if ($existingVote->vote_type === $voteType) {
                $existingVote->delete();
                return null;
            } else {
                $existingVote->update(['vote_type' => $voteType]);
                return $existingVote;
            }
        }

        return $question->votes()->create([
            'user_id' => $user->id,
            'vote_type' => $voteType,
        ]);
    }

    public function voteAnswer(Answer $answer, User $user, string $voteType): ?AnswerVote
    {
        // Check if user already voted
        $existingVote = $answer->votes()->where('user_id', $user->id)->first();

        if ($existingVote) {
            if ($existingVote->vote_type === $voteType) {
                // Remove vote if same type
                $existingVote->delete();
                return null;
            } else {
                // Update vote type
                $existingVote->update(['vote_type' => $voteType]);
                return $existingVote;
            }
        }

        return $answer->votes()->create([
            'user_id' => $user->id,
            'vote_type' => $voteType,
        ]);
    }

    public function removeQuestionVote(Question $question, User $user): bool
    {
        return $question->votes()
            ->where('user_id', $user->id)
            ->delete() > 0;
    }


    public function removeAnswerVote(Answer $answer, User $user): bool
    {
        return $answer->votes()
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    public function markAnswerHelpful(Answer $answer): Answer
    {
        $answer->increment('helpful_count');
        return $answer->fresh();
    }

    public function getQuestionVoteScore(Question $question): int
    {
        return $question->upvotesCount() - $question->downvotesCount();
    }


    public function getAnswerVoteScore(Answer $answer): int
    {
        return $answer->upvotesCount() - $answer->downvotesCount();
    }
}

