<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Tests\TestCase;

class ChatClearConversationTest extends TestCase
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
    public function it_can_clear_a_conversation_via_chat_controller()
    {
        Chat::message('Hello')
            ->from($this->user1)
            ->to($this->conversation)
            ->send();

        $this->assertEquals(1, Chat::conversation($this->conversation)->setParticipant($this->user1)->getMessages()->count());

        $response = $this->actingAs($this->user1, 'api')
            ->deleteJson("/api/v1/chat/conversations/{$this->conversation->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Conversation cleared successfully.']);

        $this->assertEquals(0, Chat::conversation($this->conversation)->setParticipant($this->user1)->getMessages()->count());
        // User 2 should still have the message
        $this->assertEquals(1, Chat::conversation($this->conversation)->setParticipant($this->user2)->getMessages()->count());
    }
}
