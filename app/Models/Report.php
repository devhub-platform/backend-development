<?php

namespace App\Models;

use App\Observers\ReportObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ReportObserver::class])]
class Report extends Model
{
    protected $fillable = [
        'message',
        'status',
        'reporter_id',
        'reported_user_id',
        'reported_post_id',
        'type',
        'reason',
        'report',
    ];

    public const REASONS = [
        'spam' => 'Spam or misleading',
        'harassment' => 'Harassment or bullying',
        'hate_speech' => 'Hate speech or discrimination',
        'violence' => 'Violence or dangerous content',
        'adult_content' => 'Adult or explicit content',
        'copyright' => 'Copyright violation',
        'misinformation' => 'False information',
        'other' => 'Other',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function reportedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'reported_post_id');
    }

    /**
     * Scope for post reports
     */
    public function scopePostReports($query)
    {
        return $query->where('type', 'post');
    }

    /**
     * Scope for user reports
     */
    public function scopeUserReports($query)
    {
        return $query->where('type', 'user');
    }
}
