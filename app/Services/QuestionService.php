<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionView;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuestionService
{
    public function __construct(
        private HackClubCdnService $cdn,
    ) {}

    public function createQuestion(User $user, array $data): Question
    {
        $data['user_id'] = $user->id;
        $data['slug']    = Str::slug($data['title']) . '-' . uniqid();

        $tags   = $data['tags']   ?? [];
        $images = $data['images'] ?? [];

        // Remove non-fillable fields before create
        unset($data['tags'], $data['images']);

        $question = Question::create($data);

        // ─── Attach Tags ──────────────────────────────────────────────────────
        if (!empty($tags)) {
            $tagIds = collect($tags)->map(function ($name) {
                return Tag::firstOrCreate(
                    ['name' => strtolower(trim($name))],
                    ['slug' => Str::slug($name)]
                )->id;
            });

            $question->tags()->sync($tagIds);
        }

        // ─── Upload Images ────────────────────────────────────────────────────
        if (!empty($images)) {
            foreach ($images as $image) {
                try {
                    $url    = $this->cdn->uploadFileUrl($image);
                    $fileId = $this->extractFileId($url);

                    $question->images()->create([
                        'url'     => $url,
                        'file_id' => $fileId,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Question image upload failed', [
                        'question_id' => $question->id,
                        'error'       => $e->getMessage(),
                    ]);
                }
            }
        }

        $question->load(['user', 'tags', 'images']);
        return $question->fresh();
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
        int     $perPage    = 14,
        string  $sortBy     = 'recent',
        ?bool   $isResolved = null,
        ?int    $postId     = null,
        ?string $tag        = null
    ): LengthAwarePaginator {
        $query = Question::query()->with(['user', 'post', 'votes', 'answers', 'tags', 'images']);

        if ($isResolved !== null) {
            $query->where('is_resolved', $isResolved);
        }

        if ($postId) {
            $query->where('post_id', $postId);
        }

        if ($tag) {
            $query->whereHas('tags', fn($q) => $q->where('name', $tag));
        }

        match ($sortBy) {
            'votes'      => $query->orderByRaw('(SELECT COUNT(*) FROM question_votes WHERE question_votes.question_id = questions.id AND vote_type = "upvote") - (SELECT COUNT(*) FROM question_votes WHERE question_votes.question_id = questions.id AND vote_type = "downvote") DESC'),
            'views'      => $query->popular(),
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
            'votes',
            'tags',
            'images',
            'answers' => fn($q) => $q
                ->orderByRaw('is_accepted DESC')
                ->orderByRaw('helpful_count DESC')
                ->orderBy('created_at', 'desc'),
            'answers.user',
            'answers.votes',
        ]);
    }

    public function acceptAnswer(Question $question, int $answerId): Question
    {
        $question->answers()->where('id', $answerId)->update(['is_accepted' => true]);

        if (!$question->is_resolved) {
            $question->update(['is_resolved' => true]);
        }

        return $question->fresh();
    }

    public function unacceptAnswer(Question $question, int $answerId): Question
    {
        $question->answers()->where('id', $answerId)->update(['is_accepted' => false]);

        $hasAccepted = $question->answers()->where('is_accepted', true)->exists();

        if (!$hasAccepted) {
            $question->update(['is_resolved' => false]);
        }

        return $question->fresh();
    }

    public function searchQuestions(string $query, int $perPage = 14): LengthAwarePaginator
    {
        return Question::where(function ($q) use ($query) {
            $q->whereRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE)", [$query])
                ->orWhere('title', 'like', "%{$query}%")
                ->orWhere('content', 'like', "%{$query}%");
        })
            ->with(['user', 'post', 'votes', 'answers', 'tags', 'images'])
            ->recent()
            ->paginate($perPage);
    }

    public function getUserQuestions(User $user, int $perPage = 14): LengthAwarePaginator
    {
        return $user->questions()
            ->with(['user', 'post', 'votes', 'answers', 'tags', 'images'])
            ->recent()
            ->paginate($perPage);
    }

    public function getUserAnsweredQuestions(User $user, int $perPage = 14): LengthAwarePaginator
    {
        return Question::whereIn('id', function ($query) use ($user) {
            $query->select('question_id')->from('answers')->where('user_id', $user->id);
        })
            ->with(['user', 'post', 'votes', 'answers', 'tags', 'images'])
            ->recent()
            ->paginate($perPage);
    }

    public function getTrendingQuestions(int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {
        return Question::with(['user', 'votes', 'answers', 'tags', 'images'])
            ->hot()
            ->limit($limit)
            ->get();
    }

    private function trackView(Question $question, ?int $userId): void
    {
        if (!$userId) {
            $question->increment('views');
            return;
        }

        $created = false;

        DB::transaction(function () use ($question, $userId, &$created) {
            $result  = QuestionView::firstOrCreate(
                ['question_id' => $question->id, 'user_id' => $userId],
                ['viewed_at'   => now()]
            );
            $created = $result->wasRecentlyCreated;
        });

        if ($created) {
            $question->increment('views');
        }
    }

    private function extractFileId(string $url): ?string
    {
        $parts = explode('/', $url);
        return end($parts) ?: null;
    }
}
