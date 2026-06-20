<?php

namespace App\Models;

use Binafy\LaravelReaction\Contracts\HasReaction;
use Binafy\LaravelReaction\Traits\Reactable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model implements HasReaction
{
    use HasFactory, SoftDeletes, Reactable;

    protected $table = 'comments';
    protected $fillable = [
        'post_id',
        'user_id',
        'content',
        'parent_id',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get all nested replies recursively
     */
    public function allReplies()
    {
        return $this->hasMany(Comment::class, 'parent_id')->with('allReplies');
    }

    /**
     * Scope to get only top-level comments (no parent)
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get pinned comments first
     */
    public function scopePinnedFirst($query)
    {
        return $query->orderByDesc('is_pinned');
    }

    /**
     * Check if comment is a reply
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Get the depth of the comment in the thread
     */
    public function getDepth(): int
    {
        $depth = 0;
        $comment = $this;
        while ($comment->parent_id !== null) {
            $depth++;
            $comment = $comment->parent;
        }
        return $depth;
    }
}
