<?php

namespace App\Models;

use App\Observers\PostObserver;
use Binafy\LaravelReaction\Contracts\HasReaction;
use Binafy\LaravelReaction\Traits\Reactable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([PostObserver::class])]
class Post extends Model implements HasReaction
{
    use HasApiTokens, HasFactory, AuthorizesRequests, Searchable, SoftDeletes, Reactable;

    protected $table = 'posts';
    protected $fillable = [
        'user_id',
        'title',
        'id',
        'slug',
        'image_url',
        'content',
        'status',
        'read_time',
        'cover_image',
        'views',
        'is_edit'
    ];

    protected $casts = [
        'views' => 'integer',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
        ];
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags', 'post_id', 'tag_id')
            ->withTimestamps();
    }

    public function postViews(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    public function getUniqueViewersCountAttribute(): int
    {
        return $this->postViews()->distinct('user_id')->count('user_id');
    }

    public function savedBy()
    {
        return $this->belongsToMany(User::class, 'saved_posts')
            ->withTimestamps();
    }

    public function readingLists()
    {
        return $this->belongsToMany(ReadingList::class, 'reading_list_story', 'post_id', 'reading_list_id')
            ->withTimestamps();
    }


    public function viewedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'post_views',
            'post_id',
            'user_id'
        )
            ->withPivot('viewed_at')
            ->withTimestamps()
            ->orderByPivot('viewed_at', 'desc');
    }
}
