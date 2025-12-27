<?php

namespace App\Http\Resources;

use App\Models\ReadingList;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

/** @mixin ReadingList */class ReadingListResource extends JsonResource{
    public function toArray(Request $request): array
    {
        return [
'id' => $this->id,
'title' => $this->title,
'description' => $this->description,
'created_at' => $this->created_at,
'updated_at' => $this->updated_at,

'user_id' => $this->user_id,

'user' => new SearchUsersResource($this->whenLoaded('user')),//
        ];
    }
}
