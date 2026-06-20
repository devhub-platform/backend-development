<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\HackClubCdnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Tests\TestCase;
use Mockery;

class ChatS3AttachmentTest extends TestCase
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
    public function it_can_send_an_attachment_to_hackclub_cdn()
    {
        $mockCdn = Mockery::mock(HackClubCdnService::class);
        $mockCdn->shouldReceive('uploadFileUrl')
            ->once()
            ->andReturn('https://cdn.hackclub.com/sample.jpg');

        $this->app->instance(HackClubCdnService::class, $mockCdn);

        $file = UploadedFile::fake()->image('test_image.jpg');

        $response = $this->actingAs($this->user1, 'api')
            ->postJson("/api/v1/messages/conversation/{$this->conversation->id}/send-attachment", [
                'file' => $file,
                'file_name' => 'Custom Name'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Attachment sent successfully.')
            ->assertJsonPath('data.attachment.type', 'attachment');

        $this->assertEquals('Custom Name', $response->json('data.attachment.data.file_name'));
        $this->assertEquals('https://cdn.hackclub.com/sample.jpg', $response->json('data.attachment.data.file_url'));
    }

    /** @test */
    public function it_can_send_a_pdf_attachment_to_hackclub_cdn()
    {
        $mockCdn = Mockery::mock(HackClubCdnService::class);
        $mockCdn->shouldReceive('uploadFileUrl')
            ->once()
            ->andReturn('https://cdn.hackclub.com/test.pdf');

        $this->app->instance(HackClubCdnService::class, $mockCdn);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user1, 'api')
            ->postJson("/api/v1/messages/conversation/{$this->conversation->id}/send-attachment", [
                'file' => $file
            ]);

        $response->assertStatus(201);

        $this->assertEquals('test.pdf', $response->json('data.attachment.data.file_name'));
        $this->assertEquals('https://cdn.hackclub.com/test.pdf', $response->json('data.attachment.data.file_url'));
    }
}
