<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionView;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuestionService
{
    private const CACHE_TTL     = 60;      // list pages — 1 minute
    private const CACHE_TTL_HOT = 300;     // trending — 5 minutes

    public function __construct(private HackClubCdnService $cdn) {}

    // ─── Create ───────────────────────────────────────────────────────────────

    public function createQuestion(User $user, array $data): Question
    {
        $data['user_id'] = $user->id;
        $data['slug']    = Str::slug($data['title']) . '-' . uniqid();

        $tags   = $data['tags']   ?? [];
        $images = $data['images'] ?? [];
        unset($data['tags'], $data['images']);

        $question = Question::create($data);

        if (!empty($tags)) {
            $question->tags()->sync($this->resolveTagIds($tags));
        }

        if (!empty($images)) {
            $this->uploadImages($question, $images, 'create');
        }

        $this->invalidateListCache();

        return $question->load(['user', 'tags', 'images']);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    /**
     * SIMPLIFIED: extracted image + tag logic into private helpers.
     * Removed Tag slug (not in DB). Removed fresh() — we load directly.
     */
    public function updateQuestion(Question $question, array $data): Question
    {
        // 1. slug — only if title changed
        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']) . '-' . uniqid();
        }

        // 2. tags — only if key exists in request (empty array = clear all)
        if (array_key_exists('tags', $data)) {
            $question->tags()->sync($this->resolveTagIds($data['tags'] ?? []));
            unset($data['tags']);
        }

        // 3. remove specific images
        if (!empty($data['remove_images'])) {
            $question->images()->whereIn('id', $data['remove_images'])->delete();
            unset($data['remove_images']);
        }

        // 4. add new images
        if (!empty($data['images'])) {
            $this->uploadImages($question, $data['images'], 'update');
            unset($data['images']);
        }

        // 5. update remaining fields (title, content, slug)
        $question->update($data);

        // 6. invalidate caches
        $this->invalidateListCache();
        Cache::forget("question:context:{$question->id}");

        // PERF FIX: load relations directly instead of fresh() which re-fetches the model too
        return $question->load(['user', 'tags', 'images', 'votes', 'answers']);
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function deleteQuestion(Question $question): bool
    {
        $result = $question->delete();
        $this->invalidateListCache();
        Cache::forget("question:context:{$question->id}");
        return $result;
    }

    // ─── Read ─────────────────────────────────────────────────────────────────

    public function getQuestions(
        int     $perPage    = 14,
        string  $sortBy     = 'recent',
        ?bool   $isResolved = null,
        ?int    $postId     = null,
        ?string $tag        = null
    ): LengthAwarePaginator {
        $page     = request()->integer('page', 1);
        $resolved = $isResolved === null ? 'all' : (int) $isResolved;
        $version  = (int) Cache::get('questions:cache_version', 1);
        $cacheKey = "questions:list:v{$version}:{$sortBy}:{$perPage}:{$resolved}:{$postId}:{$tag}:{$page}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use (
            $perPage, $sortBy, $isResolved, $postId, $tag
        ) {
            // PERF FIX: removed 'answers' from eager load in list view
            // answers are heavy and only needed in show() — not in index/list
            $query = Question::query()->with(['user', 'votes', 'tags', 'images']);

            if ($isResolved !== null) {
                $query->where('is_resolved', $isResolved);
            }

            if ($postId) {
                $query->where('post_id', $postId);
            }

            if ($tag) {
                $query->whereHas('tags', fn($q) => $q->where('name', strtolower(trim($tag))));
            }

            match ($sortBy) {
                // PERF FIX: use a JOIN instead of two correlated subqueries for vote sort
                'votes' => $query
                    ->leftJoin(DB::raw('(
                        SELECT question_id,
                            SUM(CASE WHEN vote_type = "upvote"   THEN 1 ELSE 0 END) -
                            SUM(CASE WHEN vote_type = "downvote" THEN 1 ELSE 0 END) AS score
                        FROM question_votes
                        GROUP BY question_id
                    ) AS vs'), 'questions.id', '=', 'vs.question_id')
                    ->orderByRaw('COALESCE(vs.score, 0) DESC')
                    ->select('questions.*'),
                'views'      => $query->popular(),
                'unanswered' => $query->unanswered()->recent(),
                'hot'        => $query->hot(),
                default      => $query->recent(),
            };

            return $query->paginate($perPage);
        });
    }

    public function getQuestionWithAnswers(Question $question, ?int $userId = null): Question
    {
        $this->trackView($question, $userId);

        return $question->load([
            'user', 'post', 'votes', 'tags', 'images',
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
        $this->invalidateListCache();
        return $question->fresh();
    }

    public function unacceptAnswer(Question $question, int $answerId): Question
    {
        $question->answers()->where('id', $answerId)->update(['is_accepted' => false]);
        if (!$question->answers()->where('is_accepted', true)->exists()) {
            $question->update(['is_resolved' => false]);
        }
        $this->invalidateListCache();
        return $question->fresh();
    }

    public function searchQuestions(string $query, int $perPage = 14): LengthAwarePaginator
    {
        return Question::whereRaw(
            "MATCH(title, content) AGAINST(? IN BOOLEAN MODE)",
            [$query . '*']
        )
            ->with(['user', 'votes', 'tags', 'images'])
            ->recent()
            ->paginate($perPage);
    }

    public function getUserQuestions(User $user, int $perPage = 14): LengthAwarePaginator
    {
        return $user->questions()
            ->with(['user', 'votes', 'tags', 'images'])
            ->recent()
            ->paginate($perPage);
    }

    public function getUserAnsweredQuestions(User $user, int $perPage = 14): LengthAwarePaginator
    {
        return Question::whereIn('id', function ($query) use ($user) {
            $query->select('question_id')->from('answers')->where('user_id', $user->id);
        })
            ->with(['user', 'votes', 'tags', 'images'])
            ->recent()
            ->paginate($perPage);
    }

    public function getTrendingQuestions(int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {
        $version = (int) Cache::get('questions:cache_version', 1);

        return Cache::remember("questions:trending:v{$version}:{$limit}", self::CACHE_TTL_HOT, function () use ($limit) {
            return Question::with(['user', 'votes', 'tags', 'images'])
                ->hot()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Warm up the cache for the most common list requests.
     * Call this from a scheduled job or after seeding.
     * e.g. in AppServiceProvider or a console command.
     */
    public function warmCache(): void
    {
        foreach (['recent', 'hot', 'votes', 'views', 'unanswered'] as $sort) {
            $this->getQuestions(perPage: 15, sortBy: $sort);
        }
        $this->getTrendingQuestions(limit: 5);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * SIMPLIFIED: resolve tag names to IDs in bulk.
     * One query per unique tag (firstOrCreate) — no slug, matches DB schema.
     */
    private function resolveTagIds(array $names): array
    {
        return collect($names)
            ->map(fn($name) => strtolower(trim($name)))
            ->filter()
            ->unique()
            ->map(fn($name) => Tag::firstOrCreate(['name' => $name])->id)
            ->values()
            ->all();
    }

    /**
     * Upload images to CDN and attach to question.
     */
    private function uploadImages(Question $question, array $images, string $context): void
    {
        foreach ($images as $image) {
            try {
                $url    = $this->cdn->uploadFileUrl($image);
                $fileId = $this->extractFileId($url);
                $question->images()->create(['url' => $url, 'file_id' => $fileId]);
            } catch (\Exception $e) {
                Log::warning("Question image {$context} upload failed", [
                    'question_id' => $question->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
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

    private function invalidateListCache(): void
    {
        Cache::increment('questions:cache_version');
    }

    private function extractFileId(string $url): ?string
    {
        $parts = explode('/', $url);
        return end($parts) ?: null;
    }
}
