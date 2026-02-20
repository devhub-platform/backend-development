<?php

namespace App\Notifications;

use App\Models\Answer;
use Illuminate\Notifications\Notification;

class NewAnswerNotification extends Notification
{
    protected $answer;

    public function __construct(Answer $answer)
    {
        $this->answer = $answer;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => 'New answer to your question: ' . $this->answer->question->title,
            'answerer_name' => $this->answer->user->name,
            'question_title' => $this->answer->question->title,
            'question_id' => $this->answer->question_id,
            'answer_id' => $this->answer->id,
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'question_id' => $this->answer->question_id,
            'answer_id' => $this->answer->id,
        ];
    }
}

