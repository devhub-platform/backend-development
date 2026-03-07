<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedPostImage extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'prompt',
        'secure_url',
        'public_id',
        'status',
    ];

    /**
     * status values:
     *   pending   — generated, not yet attached to a post
     *   confirmed — user accepted, attached to post
     *   discarded — user rejected, deleted from Cloudinary
     */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
