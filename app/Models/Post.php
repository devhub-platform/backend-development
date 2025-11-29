<?php

namespace App\Models;

use Binafy\LaravelReaction\Contracts\HasReaction;
use Binafy\LaravelReaction\Traits\Reactable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'created_at',
        'updated_at',
        'status',
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

    public function visits()
    {
        return visits($this)->relation();
    }

    public function savedBy()
    {
        return $this->belongsToMany(User::class, 'saved_posts')
            ->withTimestamps();
    }
}
