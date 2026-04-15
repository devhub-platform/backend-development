<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MfaCode extends Model
{
    protected $fillable = ['user_id', 'code', 'type', 'expires_at', 'used_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateCode(User $user, string $type = 'email'): self
    {
        // Invalidate previous codes
        $user->mfaCodes()->where('type', $type)->whereNull('used_at')->delete();

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return self::create([
            'user_id' => $user->id,
            'code' => $code,
            'type' => $type,
            'expires_at' => now()->addMinutes(10), // 10-minute expiry
        ]);
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture() && $this->used_at === null;
    }

    public function verify(): bool
    {
        if ($this->isValid()) {
            $this->update(['used_at' => now()]);
            return true;
        }
        return false;
    }
}


