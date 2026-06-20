<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = [
        'url',
        'text',
        'mime_type',
        'size',
        'type',
        'status',
        'filename',
        'user_id',
        'session_id',
        'blob_path',   // renamed from s3_path — run the migration below
        'extension',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $attachment) {
            if (!$attachment->user_id) {
                throw new \Exception('Attachment must have a user_id');
            }
        });
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AIChatSession::class, 'session_id');
    }
}
