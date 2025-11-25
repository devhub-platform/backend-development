<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class Tag extends Model
{
    use HasFactory , Searchable;

    protected $table = 'tags';
    protected $fillable = [
        'name',
        'id',
        'created_at',
        'updated_at',
    ];

    public function toSearchableArray()
    {
        return [
            'name' => $this->name,
        ];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tags', 'tag_id', 'post_id')
            ->withTimestamps();
    }
}
