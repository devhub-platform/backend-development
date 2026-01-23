<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostView extends Model
{
    protected $table = 'post_views';

    protected $fillable = [
        'user_id',
        'post_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who viewed the post
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post that was viewed
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
