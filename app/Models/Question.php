<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Question extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $table = 'questions';

    protected $fillable = [
        'user_id',
        'post_id',
        'title',
        'content',
        'slug',
        'is_resolved',
        'views',
        'answers_count',
    ];

    protected $casts = [
        'is_resolved'   => 'boolean',
        'views'         => 'integer',
        'answers_count' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function acceptedAnswers(): HasMany
    {
        return $this->hasMany(Answer::class)->where('is_accepted', true);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(QuestionVote::class);
    }

    public function questionViews(): HasMany
    {
        return $this->hasMany(QuestionView::class);
    }

    public function viewedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'question_views', 'question_id', 'user_id')
            ->withPivot('viewed_at')
            ->withTimestamps()
            ->orderByPivot('viewed_at', 'desc');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopePopular($query)
    {
        return $query->orderBy('views', 'desc');
    }

    public function scopeUnanswered($query)
    {
        return $query->where('answers_count', 0);
    }

    public function scopeHot($query)
    {
        return $query->orderByRaw('(views / NULLIF(DATEDIFF(NOW(), created_at), 0)) DESC');
    }

    // ─── Searchable ───────────────────────────────────────────────────────────

    public function toSearchableArray(): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'content'    => $this->content,
            'user_id'    => $this->user_id,
            'created_at' => $this->created_at,
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function getUniqueViewersCountAttribute(): int
    {
        return $this->questionViews()->distinct('user_id')->count('user_id');
    }

    public function upvotesCount(): int
    {
        return $this->votes()->where('vote_type', 'upvote')->count();
    }

    public function downvotesCount(): int
    {
        return $this->votes()->where('vote_type', 'downvote')->count();
    }

    public function voteScore(): int
    {
        return $this->upvotesCount() - $this->downvotesCount();
    }

    public function hasUserVoted(User $user): bool
    {
        return $this->votes()->where('user_id', $user->id)->exists();
    }

    public function getUserVote(User $user): ?string
    {
        return $this->votes()->where('user_id', $user->id)->value('vote_type');
    }
}
