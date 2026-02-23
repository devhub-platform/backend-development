<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'is_accepted' => $this->is_accepted,
            'helpful_count' => $this->helpful_count,
            'vote_score' => $this->voteScore(),
            'user_upvotes' => $this->upvotesCount(),
            'user_downvotes' => $this->downvotesCount(),
            'user' => new UserResource($this->whenLoaded('user')),
            'question_id' => $this->question_id,
            'question' => new QuestionResource($this->whenLoaded('question')),
            'current_user_vote' => $request->user() ? $this->getUserVote($request->user()) : null,
            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

