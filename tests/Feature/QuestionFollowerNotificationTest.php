<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\QuestionCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuestionFollowerNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_question_notification_to_followers_when_question_is_created(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $follower = User::factory()->create();
        $nonFollower = User::factory()->create();

        // follower follows author
        $follower->following()->attach($author->id);

        $response = $this->actingAs($author, 'api')->postJson('/api/v1/questions/create', [
            'title' => 'What is new in Laravel 12?',
            'content' => 'I want a detailed summary of the biggest changes and features in Laravel 12.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Question created successfully');

        Notification::assertSentTo(
            [$follower],
            QuestionCreatedNotification::class
        );

        Notification::assertNotSentTo(
            [$nonFollower],
            QuestionCreatedNotification::class
        );
    }

    public function test_it_respects_follower_notification_preference_when_question_is_created(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $followerEnabled = User::factory()->create();
        $followerDisabled = User::factory()->create();

        $followerEnabled->following()->attach($author->id);
        $followerDisabled->following()->attach($author->id);
        $followerDisabled->update([
            'notification_preferences' => [
                'new_post_from_following' => false,
            ],
        ]);

        $response = $this->actingAs($author, 'api')->postJson('/api/v1/questions/create', [
            'title' => 'How does Laravel queue retry work?',
            'content' => 'Please explain failed jobs, retry behavior, and best practices for queue retries.',
        ]);

        $response->assertStatus(201);

        Notification::assertSentTo(
            [$followerEnabled],
            QuestionCreatedNotification::class
        );

        Notification::assertNotSentTo(
            [$followerDisabled],
            QuestionCreatedNotification::class
        );
    }
}

