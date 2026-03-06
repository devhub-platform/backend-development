<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Calculate from already-loaded collection - zero extra queries
        $votes     = $this->whenLoaded('votes');
        $upvotes   = $votes ? $this->votes->where('vote_type', 'upvote')->count() : 0;
        $downvotes = $votes ? $this->votes->where('vote_type', 'downvote')->count() : 0;

        // Current user vote from loaded collection - zero extra queries
        $currentUserVote = null;
        if ($request->user() && $votes) {
            $currentUserVote = $this->votes
                ->where('user_id', $request->user()->id)
                ->first()?->vote_type;
        }

        return [
            'id'               => $this->id,
            'content'          => $this->content,
            'is_accepted'      => $this->is_accepted,
            'helpful_count'    => $this->helpful_count,
            'vote_score'       => $upvotes - $downvotes,
            'user_upvotes'     => $upvotes,
            'user_downvotes'   => $downvotes,
            'current_user_vote'=> $currentUserVote,
            'user'             => new UserResource($this->whenLoaded('user')),
            'question_id'      => $this->question_id,
            'question'         => new QuestionResource($this->whenLoaded('question')),
            'created_at'       => $this->created_at->diffForHumans(),
            'updated_at'       => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
