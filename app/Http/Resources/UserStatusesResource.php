<?php

namespace App\Http\Resources;

use App\Models\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserStatus */
class UserStatusesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'emoji' => $this->emoji,
            'status_text' => $this->status_text,
            'is_busy' => $this->is_busy,
            'clear_after' => $this->clear_after?->format('Y-m-d H:i:s'),
            'is_expired' => $this->isExpired(),
            'expires_in_minutes' => $this->getExpiresInMinutes(),
            'created_at' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->diffForHumans(),
        ];
    }

    /**
     * Check if status is expired
     */
    private function isExpired(): bool
    {
        return $this->clear_after && now()->isAfter($this->clear_after);
    }

    /**
     * Get remaining minutes until expiration
     */
    private function getExpiresInMinutes(): ?int
    {
        if (!$this->clear_after) {
            return null;
        }

        $minutes = now()->diffInMinutes($this->clear_after, false);
        return max(0, $minutes);
    }
}
