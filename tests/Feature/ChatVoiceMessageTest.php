<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\HackClubCdnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Tests\TestCase;

class ChatVoiceMessageTest extends TestCase
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
    public function it_can_send_a_voice_message_to_hackclub_cdn(): void
    {
        $mockCdn = Mockery::mock(HackClubCdnService::class);
        $mockCdn->shouldReceive('uploadFileUrl')
            ->once()
            ->andReturn('https://cdn.hackclub.com/voice.webm');

        $this->app->instance(HackClubCdnService::class, $mockCdn);

        $file = UploadedFile::fake()->create('voice.webm', 400, 'audio/webm');

        $response = $this->actingAs($this->user1, 'api')
            ->post("/api/v1/messages/conversation/{$this->conversation->id}/send-voice", [
                'file' => $file,
                'file_name' => 'intro-voice.webm',
                'duration_ms' => 2200,
                'message' => 'Quick update',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Voice message sent successfully.')
            ->assertJsonPath('data.type', 'voice')
            ->assertJsonPath('data.data.file_name', 'intro-voice.webm')
            ->assertJsonPath('data.data.file_url', 'https://cdn.hackclub.com/voice.webm')
            ->assertJsonPath('data.data.duration_ms', 2200);
    }

    /** @test */
    public function it_rejects_non_audio_voice_uploads(): void
    {
        $file = UploadedFile::fake()->create('notes.txt', 50, 'text/plain');

        $response = $this->actingAs($this->user1, 'api')
            ->post("/api/v1/messages/conversation/{$this->conversation->id}/send-voice", [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_rejects_oversized_voice_uploads(): void
    {
        $file = UploadedFile::fake()->create('big-voice.webm', 12000, 'audio/webm');

        $response = $this->actingAs($this->user1, 'api')
            ->post("/api/v1/messages/conversation/{$this->conversation->id}/send-voice", [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }
}

