<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'slug' => $this->slug,
            'is_resolved' => $this->is_resolved,
            'views' => $this->views,
            'answers_count' => $this->answers_count,
            'vote_score' => $this->voteScore(),
            'user_upvotes' => $this->upvotesCount(),
            'user_downvotes' => $this->downvotesCount(),
            'user' => new UserResource($this->whenLoaded('user')),
//            'post' => new PostResource($this->whenLoaded('post')),
            'accepted_answer' => new AnswerResource($this->whenLoaded('acceptedAnswer')),
            'answers' => AnswerResource::collection($this->whenLoaded('answers')),
            'current_user_vote' => $request->user() ? $this->getUserVote($request->user()) : null,
            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->diffForHumans(),
        ];
    }
}

