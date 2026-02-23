<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchHistory extends Model
{
    protected $table = 'search_histories';
    protected $fillable = [
        'user_id',
        'query',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
