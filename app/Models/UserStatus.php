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
            'clear_after' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Check if the status has expired
     */
    public function isExpired(): bool
    {
        return $this->clear_after && now()->isAfter($this->clear_after);
    }

    /**
     * Check if the status is active (not expired)
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Get remaining time until expiration in minutes
     */
    public function getExpiresInMinutes(): ?int
    {
        if (!$this->clear_after) {
            return null;
        }

        $minutes = now()->diffInMinutes($this->clear_after, false);
        return max(0, $minutes);
    }

    /**
     * Check if user is busy
     */
    public function isBusy(): bool
    {
        return $this->is_busy && $this->isActive();
    }

    /**
     * Get full status display (emoji + text)
     */
    public function getFullStatus(): string
    {
        $status = '';

        if ($this->emoji) {
            $status .= $this->emoji . ' ';
        }

        if ($this->status_text) {
            $status .= $this->status_text;
        }

        return trim($status) ?: 'No status';
    }

    /**
     * Scope: Get active statuses only (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('clear_after')
                ->orWhere('clear_after', '>', now());
        });
    }

    /**
     * Scope: Get expired statuses
     */
    public function scopeExpired($query)
    {
        return $query->where('clear_after', '<=', now())
            ->whereNotNull('clear_after');
    }

    /**
     * Scope: Get busy statuses
     */
    public function scopeBusy($query)
    {
        return $query->where('is_busy', true)->active();
    }

    /**
     * Mark status as busy
     */
    public function markBusy(string $text = null, \DateTime $clearAfter = null): self
    {
        $this->is_busy = true;
        $this->status_text = $text ?? $this->status_text ?? 'Busy';
        $this->clear_after = $clearAfter;
        $this->save();

        return $this;
    }

    /**
     * Mark status as available
     */
    public function markAvailable(): self
    {
        $this->is_busy = false;
        $this->clear_after = null;
        $this->save();

        return $this;
    }

    /**
     * Clear the status
     */
    public function clearStatus(): bool
    {
        return $this->delete();
    }
}
