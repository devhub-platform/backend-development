<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class AnswerService
{
    public function createAnswer(Question $question, User $user, string $content): Answer
    {
        $answer = $question->answers()->create([
            'user_id' => $user->id,
            'content' => $content,
        ]);

        $question->increment('answers_count');

        return $answer->load(['user', 'votes']);
    }

    public function updateAnswer(Answer $answer, string $content): Answer
    {
        $answer->update(['content' => $content]);
        return $answer->fresh()->load(['user', 'votes']);
    }

    public function deleteAnswer(Answer $answer): bool
    {
        $answer->question->decrement('answers_count');
        return $answer->delete();
    }

    /**
     * Get answers sorted: accepted first → most helpful → newest
     */
    public function getQuestionAnswers(Question $question, int $perPage = 10): LengthAwarePaginator
    {
        return $question->answers()
            ->with(['user', 'votes'])
            ->orderByRaw('is_accepted DESC')
            ->orderByRaw('helpful_count DESC')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getUserAnswers(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->answers()
            ->with(['user', 'question', 'votes'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getUserAcceptedAnswers(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->answers()
            ->where('is_accepted', true)
            ->with(['user', 'question', 'votes'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getUserAnswerCount(User $user): int
    {
        return $user->answers()->count();
    }

    public function getUserAcceptedAnswerCount(User $user): int
    {
        return $user->answers()->where('is_accepted', true)->count();
    }
}
