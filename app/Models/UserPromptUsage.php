<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPromptUsage extends Model
{
    protected $table = 'user_prompt_usage';

    protected $fillable = [
        'user_id',
        'daily_count',
        'monthly_count',
        'last_daily_reset',
        'last_monthly_reset',
    ];

    protected $casts = [
        'last_daily_reset'   => 'date:Y-m-d',
        'last_monthly_reset' => 'date:Y-m-d',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
