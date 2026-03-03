<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AWSS3Service;
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
    public function it_can_send_an_attachment_to_s3()
    {
        $mockS3 = Mockery::mock(AWSS3Service::class);
        $mockS3->shouldReceive('uploadFile')
            ->once()
            ->andReturn('https://bucket.s3.region.amazonaws.com/chat_attachments/sample.jpg');

        $this->app->instance(AWSS3Service::class, $mockS3);

        $file = UploadedFile::fake()->image('test_image.jpg');

        $response = $this->actingAs($this->user1, 'api')
            ->postJson("/api/v1/messages/{$this->conversation->id}/send-attachment", [
                'file' => $file,
                'file_name' => 'Custom Name'
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Attachment sent.',
                'data' => [
                    'body' => 'Attachment',
                    'type' => 'attachment',
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertEquals('Custom Name', $responseData['data']['file_name']);
        $this->assertEquals('https://bucket.s3.region.amazonaws.com/chat_attachments/sample.jpg', $responseData['data']['file_url']);
    }

    /** @test */
    public function it_can_send_a_pdf_attachment_to_s3()
    {
        $mockS3 = Mockery::mock(AWSS3Service::class);
        $mockS3->shouldReceive('uploadFile')
            ->once()
            ->andReturn('https://bucket.s3.region.amazonaws.com/chat_attachments/test.pdf');

        $this->app->instance(AWSS3Service::class, $mockS3);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user1, 'api')
            ->postJson("/api/v1/messages/{$this->conversation->id}/send-attachment", [
                'file' => $file
            ]);

        $response->assertStatus(201);

        $responseData = $response->json('data');
        $this->assertEquals('test.pdf', $responseData['data']['file_name']);
        $this->assertEquals('https://bucket.s3.region.amazonaws.com/chat_attachments/test.pdf', $responseData['data']['file_url']);
    }
}
