<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnswerVote extends Model
{
    use HasFactory;

    protected $table = 'answer_votes';

    protected $fillable = [
        'answer_id',
        'user_id',
        'vote_type',
    ];

    public $timestamps = true;

    // Relationships
    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

