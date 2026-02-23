<?php

namespace App\Http\Requests\ProfileRequests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_follower' => ['sometimes', 'boolean'],
            'new_comment' => ['sometimes', 'boolean'],
            'new_reaction' => ['sometimes', 'boolean'],
            'new_post_from_following' => ['sometimes', 'boolean'],
            'mention' => ['sometimes', 'boolean'],
            'question_answered' => ['sometimes', 'boolean'],
            'weekly_digest' => ['sometimes', 'boolean'],
            'chat_message' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_follower.boolean' => 'new_follower must be a boolean value',
            'new_comment.boolean' => 'new_comment must be a boolean value',
            'new_reaction.boolean' => 'new_reaction must be a boolean value',
            'new_post_from_following.boolean' => 'new_post_from_following must be a boolean value',
            'mention.boolean' => 'mention must be a boolean value',
            'question_answered.boolean' => 'question_answered must be a boolean value',
            'weekly_digest.boolean' => 'weekly_digest must be a boolean value',
            'chat_message.boolean' => 'chat_message must be a boolean value',
        ];
    }
}

