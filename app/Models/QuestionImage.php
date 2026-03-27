<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionImage extends Model
{
    protected $fillable = [
        'question_id',
        'url',
        'file_id',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
