<?php

namespace App\Policies;

use App\Models\Answer;
use App\Models\User;

class AnswerPolicy
{
    public function view(User $user, Answer $answer): bool
    {
        return true; // Answers are publicly viewable
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can answer
    }

    public function update(User $user, Answer $answer): bool
    {
        return $user->id === $answer->user_id;
    }

    public function delete(User $user, Answer $answer): bool
    {
        return $user->id === $answer->user_id;
    }

    public function accept(User $user, Answer $answer): bool
    {
        return $user->id === $answer->question->user_id;
    }

    public function vote(User $user, Answer $answer): bool
    {
        return $user->id !== $answer->user_id; // Can't vote on own answer
    }

    public function removeVote(User $user, Answer $answer): bool
    {
        return true;
    }
}

