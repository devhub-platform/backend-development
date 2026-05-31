<?php

namespace App\Notifications;

use App\Models\Question;
use Illuminate\Notifications\Notification;

class QuestionCreatedNotification extends Notification
{
    protected $question;

    public function __construct(Question $question)
    {
        $this->question = $question;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'New question: ' . $this->question->title,
            'asker_id' => $this->question->user->id,
            'asker_name' => $this->question->user->name,
            'asker_username' => $this->question->user->username,
            'asker_avatar' => $this->question->user->avatar_url,
            'question_title' => $this->question->title,
            'question_id' => $this->question->id,
            'post_id' => $this->question->post_id,
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'question_id' => $this->question->id,
        ];
    }
}

