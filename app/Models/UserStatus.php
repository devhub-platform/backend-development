<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStatus extends Model
{
    protected $table = 'user_statuses';
    protected $fillable = [
        'user_id',
        'emoji',
        'status_text',
        'is_busy',
        'clear_after',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'is_busy' => 'boolean',
        ];
    }
}
