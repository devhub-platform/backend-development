<?php

namespace Tests\Unit;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Notifications\NewAnswerNotification;
use Tests\TestCase;

class NewAnswerNotificationTest extends TestCase
{
    public function test_it_builds_a_safe_database_payload(): void
    {
        $question = new Question();
        $question->id = 7;
        $question->title = 'How to cache in Laravel?';

        $answerer = new User();
        $answerer->id = 15;
        $answerer->name = 'Answer User';
        $answerer->username = 'answer_user';
        $answerer->avatar_url = 'https://example.com/avatar.png';

        $answer = new Answer();
        $answer->id = 22;
        $answer->question_id = 7;
        $answer->setRelation('question', $question);
        $answer->setRelation('user', $answerer);

        $notification = new NewAnswerNotification($answer);
        $data = $notification->toDatabase(new User());

        $this->assertSame('New answer to your question: How to cache in Laravel?', $data['message']);
        $this->assertSame('How to cache in Laravel?', $data['question_title']);
        $this->assertSame(7, $data['question_id']);
        $this->assertSame(22, $data['answer_id']);
        $this->assertSame([
            'id' => 15,
            'name' => 'Answer User',
            'username' => 'answer_user',
            'avatar_url' => 'https://example.com/avatar.png',
        ], $data['answerer_from_user']);
    }

    public function test_it_sets_answerer_to_null_when_user_is_missing(): void
    {
        $question = new Question();
        $question->title = 'Question title';

        $answer = new Answer();
        $answer->question_id = 9;
        $answer->id = 99;
        $answer->setRelation('question', $question);
        $answer->setRelation('user', null);

        $notification = new NewAnswerNotification($answer);
        $data = $notification->toDatabase(new User());

        $this->assertNull($data['answerer_from_user']);
        $this->assertSame(['question_id' => 9, 'answer_id' => 99], $notification->toArray(new User()));
    }
}

