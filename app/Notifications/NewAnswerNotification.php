<?php

namespace App\Notifications;

use App\Models\Answer;
use Illuminate\Notifications\Notification;

class NewAnswerNotification extends Notification
{
    protected Answer $answer;
    protected array $databasePayload;

    public function __construct(Answer $answer)
    {
        $this->answer = $answer;
        $this->databasePayload = $this->buildDatabasePayload();
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return $this->databasePayload;
    }

    public function toArray($notifiable): array
    {
        return [
            'question_id' => $this->databasePayload['question_id'],
            'answer_id' => $this->databasePayload['answer_id'],
        ];
    }

    private function buildDatabasePayload(): array
    {
        $answer = $this->answer->loadMissing(['question', 'user']);
        $question = $answer->question;

        return [
            'message' => 'New answer to your question: ' . ($question->title ?? ''),
            'answerer_from_user' => $this->answererPayload($answer->user),
            'question_title' => $question->title ?? null,
            'question_id' => $answer->question_id,
            'answer_id' => $answer->id,
        ];
    }

    private function answererPayload($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
        ];
    }
}

