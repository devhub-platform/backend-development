<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function view(User $user, Question $question): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Question $question): bool
    {
        return $user->id === $question->user_id;
    }

    public function delete(User $user, Question $question): bool
    {
        return $user->id === $question->user_id;
    }

    public function vote(User $user, Question $question): bool
    {
        return $user->id !== $question->user_id; // Can't vote on own question
    }

    public function removeVote(User $user, Question $question): bool
    {
        return true;
    }
}

