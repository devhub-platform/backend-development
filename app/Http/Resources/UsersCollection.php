<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see \App\Models\User */
class UsersCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'total' => $this->collection->count(),
            'data' => $this->collection,
        ];
    }
}
