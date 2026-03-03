<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Tests\TestCase;

class ChatMessageActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
        $this->conversation = Chat::makeDirect()->createConversation([$this->user1, $this->user2]);
    }

    /** @test */
    public function it_can_delete_a_message()
    {
        $message = Chat::message('Test message')
            ->from($this->user1)
            ->to($this->conversation)
            ->send();

        $response = $this->actingAs($this->user1, 'api')
            ->deleteJson("/api/v1/messages/{$this->conversation->id}/{$message->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Message deleted.']);
    }

    /** @test */
    public function it_can_react_to_a_message()
    {
        $message = Chat::message('Test message')
            ->from($this->user1)
            ->to($this->conversation)
            ->send();

        $response = $this->actingAs($this->user2, 'api')
            ->postJson("/api/v1/messages/{$this->conversation->id}/{$message->id}/reaction", [
                'reaction' => '👍'
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Reaction added to message.']);
    }

    /** @test */
    public function it_can_unreact_to_a_message()
    {
        $message = Chat::message('Test message')
            ->from($this->user1)
            ->to($this->conversation)
            ->send();

        Chat::message($message)->setParticipant($this->user2)->react('👍');

        $response = $this->actingAs($this->user2, 'api')
            ->deleteJson("/api/v1/messages/{$this->conversation->id}/{$message->id}/reaction", [
                'reaction' => '👍'
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Reaction removed from message.']);
    }

    /** @test */
    public function it_can_toggle_a_reaction()
    {
        $message = Chat::message('Test message')
            ->from($this->user1)
            ->to($this->conversation)
            ->send();

        // Add
        $response = $this->actingAs($this->user2, 'api')
            ->postJson("/api/v1/messages/{$this->conversation->id}/{$message->id}/toggle-reaction", [
                'reaction' => '❤️'
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Reaction added.']);

        // Remove
        $response = $this->actingAs($this->user2, 'api')
            ->postJson("/api/v1/messages/{$this->conversation->id}/{$message->id}/toggle-reaction", [
                'reaction' => '❤️'
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Reaction removed.']);
    }

    /** @test */
    public function it_can_get_reactions_summary()
    {
        $message = Chat::message('Test message')
            ->from($this->user1)
            ->to($this->conversation)
            ->send();

        Chat::message($message)->setParticipant($this->user1)->react('👍');
        Chat::message($message)->setParticipant($this->user2)->react('👍');
        Chat::message($message)->setParticipant($this->user2)->react('❤️');

        $response = $this->actingAs($this->user1, 'api')
            ->getJson("/api/v1/messages/{$this->conversation->id}/{$message->id}/reactions-summary");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    '👍' => 2,
                    '❤️' => 1
                ]
            ]);
    }
}
