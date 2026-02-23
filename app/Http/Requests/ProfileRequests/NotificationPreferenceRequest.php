<?php

namespace App\Http\Requests\ProfileRequests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:new_follower,new_comment,new_reaction,new_post_from_following,mention', 'equations', 'answers', 'weekly_digest', 'chat_message'],
            'enabled' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Notification type is required',
            'type.string' => 'Notification type must be a string',
            'type.in' => 'Invalid notification type',
            'enabled.required' => 'Enabled field is required',
            'enabled.boolean' => 'Enabled field must be a boolean value',
        ];
    }
}

