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

    public function session()
    {
        return $this->belongsTo(AIChatSession::class, 'ai_chat_session_id');
    }
}
