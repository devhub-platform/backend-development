<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Answer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'answers';

    protected $fillable = [
        'question_id',
        'user_id',
        'content',
        'is_accepted',
        'helpful_count',
    ];

    protected $casts = [
        'is_accepted'   => 'boolean',
        'helpful_count' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(AnswerVote::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeAccepted($query)
    {
        return $query->where('is_accepted', true);
    }

    public function scopeNotAccepted($query)
    {
        return $query->where('is_accepted', false);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByMostHelpful($query)
    {
        return $query->orderBy('helpful_count', 'desc');
    }

    public function scopeTopVoted($query)
    {
        return $query->orderByRaw('(
            SELECT COUNT(*) FROM answer_votes
            WHERE answer_votes.answer_id = answers.id AND vote_type = "upvote"
        ) - (
            SELECT COUNT(*) FROM answer_votes
            WHERE answer_votes.answer_id = answers.id AND vote_type = "downvote"
        ) DESC');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

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
