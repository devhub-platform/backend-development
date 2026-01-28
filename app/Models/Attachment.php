<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = [
        'url',
        'text',
        'filename',
        'user_id',
        'session_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attachment) {
            if (!$attachment->user_id) {
                throw new \Exception('Attachment must have a user_id');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
