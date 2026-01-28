<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AIChatSession extends Model
{
    protected $table = 'ai_chat_sessions';

    protected $fillable = [
        'user_id',
        'title',
        'model',
        'pinned',
        'active',
        'closed_at'
    ];

    protected $casts = [
        'pinned' => 'boolean',
        'active' => 'boolean',
        'closed_at' => 'datetime'
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AIChatMessage::class, 'ai_chat_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
