<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'views' => 'integer',
        'answers_count' => 'integer',
    ];

    // Relationships
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

    public function acceptedAnswer(): BelongsTo
    {
        return $this->belongsTo(Answer::class, 'accepted_answer_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(QuestionVote::class);
    }

    // Scopes
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

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
        ];
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
        return $this->votes()
            ->where('user_id', $user->id)
            ->value('vote_type');
    }
}

