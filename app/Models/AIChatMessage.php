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
        'attachments' => 'array',
    ];

    /**
     * Always return an array for attachments, even if the DB column is null.
     * Prevents crashes in the chat layer when iterating over older messages.
     */
    public function getAttachmentsAttribute($value): array
    {
        if (is_null($value)) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function session()
    {
        return $this->belongsTo(AIChatSession::class, 'ai_chat_session_id');
    }
}
