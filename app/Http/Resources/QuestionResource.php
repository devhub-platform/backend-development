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
        $tags = $this->relationLoaded('tags') ? $this->tags : collect();
        $acceptedAnswers = $this->relationLoaded('acceptedAnswers') ? $this->acceptedAnswers : collect();
        $answers = $this->relationLoaded('answers') ? $this->answers : collect();

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
            'tags' => $tags->map(fn($tag) => [
                'id'   => $tag->id,
                'name' => $tag->name,
            ]),
            'images' => $this->whenLoaded('images'),
            'is_resolved' => $this->is_resolved,
            'views' => $this->views,
            'answers_count' => $this->answers_count,
            'vote_score' => $upvotes - $downvotes,
            'user_upvotes' => $upvotes,
            'user_downvotes' => $downvotes,
            'current_user_vote' => $currentUserVote,
            'user' => [
                'id'     => $this->user?->id,
                'name'   => $this->user?->name,
                'avatar' => $this->user?->avatar_url,
            ],
            'accepted_answers' => AnswerResource::collection($acceptedAnswers),
            'answers' => AnswerResource::collection($answers),
            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at->diffForHumans(),
        ];
    }
}
