<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionView;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class QuestionService
{
    public function createQuestion(User $user, array $data): Question
    {
        $data['user_id'] = $user->id;
        $data['slug']    = Str::slug($data['title']) . '-' . uniqid();

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

    public function getQuestions(
        int    $perPage    = 15,
        string $sortBy     = 'recent',
        ?bool  $isResolved = null,
        ?int   $postId     = null
    ): LengthAwarePaginator {
        $query = Question::query()->with(['user', 'post']);

        if ($isResolved !== null) {
            $query->where('is_resolved', $isResolved);
        }

        if ($postId) {
            $query->where('post_id', $postId);
        }

        match ($sortBy) {
            'popular'    => $query->popular(),
            'unanswered' => $query->unanswered()->recent(),
            'hot'        => $query->hot(),
            default      => $query->recent(),
        };

        return $query->paginate($perPage);
    }

    public function getQuestionWithAnswers(Question $question, ?int $userId = null): Question
    {
        $this->trackView($question, $userId);

        return $question->load([
            'user',
            'post',
            'answers' => fn($q) => $q
                ->orderByRaw('is_accepted DESC')
                ->orderByRaw('helpful_count DESC')
                ->orderBy('created_at', 'desc'),
            'answers.user',
            'answers.votes',
        ]);
    }

    /**
     * Accept an answer - multiple answers can be accepted
     */
    public function acceptAnswer(Question $question, int $answerId): Question
    {
        $question->answers()->where('id', $answerId)->update(['is_accepted' => true]);

        // Mark question as resolved if not already
        if (!$question->is_resolved) {
            $question->update(['is_resolved' => true]);
        }

        return $question->fresh();
    }

    /**
     * Unaccept a specific answer
     * If no more accepted answers → mark question as unresolved
     */
    public function unacceptAnswer(Question $question, int $answerId): Question
    {
        $question->answers()->where('id', $answerId)->update(['is_accepted' => false]);

        // Check if any accepted answers remain
        $hasAccepted = $question->answers()->where('is_accepted', true)->exists();

        if (!$hasAccepted) {
            $question->update(['is_resolved' => false]);
        }

        return $question->fresh();
    }

    public function searchQuestions(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Question::where(function ($q) use ($query) {
            $q->whereRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE)", [$query])
                ->orWhere('title', 'like', "%{$query}%")
                ->orWhere('content', 'like', "%{$query}%");
        })
            ->with(['user', 'post'])
            ->recent()
            ->paginate($perPage);
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
            $query->select('question_id')->from('answers')->where('user_id', $user->id);
        })
            ->with(['user', 'post'])
            ->recent()
            ->paginate($perPage);
    }

    public function getTrendingQuestions(int $perPage = 15): LengthAwarePaginator
    {
        return Question::with(['user', 'post'])
            ->hot()
            ->paginate($perPage);
    }

    private function trackView(Question $question, ?int $userId): void
    {
        if (!$userId) {
            $question->increment('views');
            return;
        }

        $alreadyViewed = QuestionView::where('question_id', $question->id)
            ->where('user_id', $userId)
            ->exists();

        if (!$alreadyViewed) {
            QuestionView::create([
                'question_id' => $question->id,
                'user_id'     => $userId,
                'viewed_at'   => now(),
            ]);
            $question->increment('views');
        }
    }
}
