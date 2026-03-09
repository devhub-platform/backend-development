<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIChatMessage extends Model
{
    protected $table = 'ai_chat_messages';

    protected $fillable = [
        'ai_chat_session_id',
        'role',
        'content',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array', // handles encoding on write + decoding on read
    ];

    /**
     * Extra safety: always return an array even if DB value is null or malformed.
     * The cast handles normal cases; this accessor covers edge cases.
     */
    public function getAttachmentsAttribute($value): array
    {
        if (is_null($value)) return [];
        if (is_array($value)) return $value;

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function session()
    {
        return $this->belongsTo(AIChatSession::class, 'ai_chat_session_id');
    }
}
