<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'ID' => $this->id,
            'Name' => $this->name,
            'Username' => $this->username,
            'Role' => $this->role ?? 'user',
            'Avatar Image' => $this->avatar_url
                ? (filter_var($this->avatar_url, FILTER_VALIDATE_URL)
                    ? $this->avatar_url
                    : asset('storage/' . $this->avatar_url))
                : null,
            'Cover Image' => $this->cover_image ? asset('storage/' . $this->cover_url) : null,
            'Bio' => $this->bio,
            'Provider ID' => $this->provider_id,
            'Email' => $this->email,
            'Email verified at' => $this->email_verified_at ? $this->email_verified_at->format('Y-m-d H:i:s') : null,
            'Join At' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'Last Update' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
