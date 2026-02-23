<?php

namespace App\Services;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class QuestionService
{

    public function createQuestion(User $user, array $data): Question
    {
        $data['user_id'] = $user->id;
        $data['slug'] = Str::slug($data['title']) . '-' . uniqid();

        return Question::create($data);
    }


    public function updateQuestion(Question $question, array $data): Question
    {
        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']) . '-' . uniqid();
        }

        $question->update($data);
        return $question->fresh();
    }


    public function deleteQuestion(Question $question): bool
    {
        return $question->delete();
    }

    /**
     * Get paginated questions with filters
     */
    public function getQuestions(
        int $perPage = 15,
        ?string $sortBy = 'recent',
        ?bool $isResolved = null,
        ?int $postId = null
    ): LengthAwarePaginator {
        $query = Question::query();

        if ($isResolved !== null) {
            $query->where('is_resolved', $isResolved);
        }

        if ($postId) {
            $query->where('post_id', $postId);
        }

        // Apply sorting
        match ($sortBy) {
            'popular' => $query->orderBy('views', 'desc'),
            'unanswered' => $query->where('answers_count', 0)->recent(),
            'trending' => $query->orderByRaw('(views / DATEDIFF(NOW(), created_at)) DESC'),
            default => $query->recent(),
        };

        return $query->with(['user', 'post'])
            ->paginate($perPage);
    }

    /**
     * Get a single question with answers
     */
    public function getQuestionWithAnswers(Question $question): Question
    {
        $question->increment('views');

        return $question->load([
            'user',
            'post',
            'answers' => fn($q) => $q
                ->orderByRaw('is_accepted DESC')
                ->orderByRaw('helpful_count DESC')
                ->orderBy('created_at', 'desc'),
            'answers.user',
            'acceptedAnswer',
            'acceptedAnswer.user',
        ]);
    }

    public function searchQuestions(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Question::whereRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE)", [$query])
            ->orWhere('title', 'ilike', "%{$query}%")
            ->orWhere('content', 'ilike', "%{$query}%")
            ->with(['user', 'post'])
            ->recent()
            ->paginate($perPage);
    }

    public function acceptAnswer(Question $question, int $answerId): Question
    {
        $question->update([
            'is_resolved' => true,
            'accepted_answer_id' => $answerId,
        ]);

        $question->answers()->where('id', $answerId)->update(['is_accepted' => true]);

        return $question->fresh();
    }

    public function unacceptAnswer(Question $question): Question
    {
        if ($question->accepted_answer_id) {
            $question->answers()
                ->where('id', $question->accepted_answer_id)
                ->update(['is_accepted' => false]);
        }

        $question->update([
            'is_resolved' => false,
            'accepted_answer_id' => null,
        ]);

        return $question->fresh();
    }

    public function getUserQuestions(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->questions()
            ->with(['user', 'post'])
            ->recent()
            ->paginate($perPage);
    }


    public function getUserAnsweredQuestions(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Question::whereIn('id', function ($query) use ($user) {
            $query->select('question_id')
                ->from('answers')
                ->where('user_id', $user->id);
        })
            ->with(['user', 'post'])
            ->recent()
            ->paginate($perPage);
    }

    public function getTrendingQuestions()
    {
        return Question::with(['user', 'post'])
            ->orderByRaw('(views / DATAFEED(NOW(), created_at)) DESC')
            ->recent()
            ->paginate(15);
    }
}

