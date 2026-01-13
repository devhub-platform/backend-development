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
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AIChatMessage::class);
    }
}
