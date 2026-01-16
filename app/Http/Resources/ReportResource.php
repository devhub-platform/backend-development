<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Report */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'status' => $this->status,
            'reporter' => [
//                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'email' => $this->reporter->email,
            ],
            'reported_user' => [
                'id' => $this->reportedUser->id,
                'name' => $this->reportedUser->name,
                'email' => $this->reportedUser->email,
            ],
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }
}
