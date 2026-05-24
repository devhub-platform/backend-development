<?php

namespace App\Models;

use App\Observers\PostObserver;
use Binafy\LaravelReaction\Contracts\HasReaction;
use Binafy\LaravelReaction\Traits\Reactable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
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
        'uuid',
        'slug',
        'image_url',
        'content',
        'status',
        'read_time',
        'cover_image',
        'views',
        'is_edit',
        'embedding',
        'embedded_at',
        'added_to_ai_at',
    ];

    protected $casts = [
        'views'         => 'integer',
        'embedding'     => 'array',
        'embedded_at'   => 'datetime',
        'added_to_ai_at'=> 'datetime',
    ];

    public function getImageUrlAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_values(array_filter($decoded));
            }

            return [$value];
        }

        return array_values(array_filter((array) $value));
    }

    public function setImageUrlAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['image_url'] = null;
            return;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = [$value];
            }
        }

        $this->attributes['image_url'] = json_encode(array_values(array_filter((array) $value)));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePrioritizeFollowedTags(Builder $query, ?User $user): Builder
    {
        if (!$user) {
            return $query;
        }

        if (!$user->followedTags()->exists()) {
            return $query;
        }

        return $query->orderByRaw(
            'EXISTS (
                SELECT 1
                FROM post_tags pt
                INNER JOIN tag_user tu ON tu.tag_id = pt.tag_id
                WHERE pt.post_id = posts.id
                  AND tu.user_id = ?
            ) DESC',
            [$user->id]
        );
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function generatedImages(): HasMany
    {
        return $this->hasMany(GeneratedPostImage::class);
    }

    public function toSearchableArray()
    {
        return [
            'id'      => $this->id,
            'title'   => $this->title,
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

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
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
