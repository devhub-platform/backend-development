<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ReadingList extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts()
    {
        return $this->belongsToMany(
            Post::class,
            'reading_list_story',
            'reading_list_id',
            'post_id'
        )->withTimestamps();
    }

}
