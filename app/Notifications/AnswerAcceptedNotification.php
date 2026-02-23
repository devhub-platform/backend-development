<?php

namespace App\Notifications;

use App\Models\Answer;
use Illuminate\Notifications\Notification;

class AnswerAcceptedNotification extends Notification
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
            'message' => 'Your answer was accepted: ' . $this->answer->question->title,
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

