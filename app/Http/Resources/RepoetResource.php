<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Report */
class RepoetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'report' => $this->report,
//            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
//            'type' => $this->type,
            'reason' => $this->reason,

//            'reporter_id' => $this->reporter_id,
//            'reported_user_id' => $this->reported_user_id,
//            'reported_post_id' => $this->reported_post_id,

//            'reportedPost' => new PostResource($this->whenLoaded('reportedPost')),
            'reportedUser' => new SearchUsersResource($this->whenLoaded('reportedUser')),
//            'reporter' => new SearchUsersResource($this->whenLoaded('reporter')),
        ];
    }
}
