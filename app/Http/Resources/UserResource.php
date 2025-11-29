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
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'role' => $this->role ?? 'user',
            'image_url' => $this->avatar_url ? asset('storage/' . $this->avatar_url) : null,
            'bio' => $this->bio,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at->format('Y-m-d H:i:s'),
            'password' => $this->password,
            'Join At' => $this->created_at->format('Y-m-d H:i:s'),
            'Last Update' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
