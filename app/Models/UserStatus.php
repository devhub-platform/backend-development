<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $emoji
 * @property string|null $status_text
 * @property bool $is_busy
 * @property string|null $clear_after
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereClearAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereEmoji($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereIsBusy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereStatusText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserStatus whereUserId($value)
 * @mixin \Eloquent
 */
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
