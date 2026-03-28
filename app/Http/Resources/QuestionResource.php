<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Calculate from already-loaded collection - zero extra queries
        $votes = $this->whenLoaded('votes');
        $upvotes = $votes ? $this->votes->where('vote_type', 'upvote')->count() : 0;
        $downvotes = $votes ? $this->votes->where('vote_type', 'downvote')->count() : 0;

        // Current user vote from loaded collection - zero extra queries
        $currentUserVote = null;
        if ($request->user() && $votes) {
            $currentUserVote = $this->votes
                ->where('user_id', $request->user()->id)
                ->first()?->vote_type;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'slug' => $this->slug,
            'tags' => $this->whenLoaded('tags'),
            'images' => $this->whenLoaded('images'),
            'is_resolved' => $this->is_resolved,
            'views' => $this->views,
            'answers_count' => $this->answers_count,
            'vote_score' => $upvotes - $downvotes,
            'user_upvotes' => $upvotes,
            'user_downvotes' => $downvotes,
            'current_user_vote' => $currentUserVote,
            'user' => new UserResource($this->whenLoaded('user')),
            'accepted_answers' => AnswerResource::collection($this->whenLoaded('acceptedAnswers')),
            'answers' => AnswerResource::collection($this->whenLoaded('answers')),
            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->diffForHumans(),
        ];
    }
}
