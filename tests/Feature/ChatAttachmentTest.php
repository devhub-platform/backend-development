<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ImageUploadCloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Tests\TestCase;
use Mockery;

class ChatAttachmentTest extends TestCase
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
    public function it_can_send_an_attachment_to_cloudinary()
    {
        $mockCloudinary = Mockery::mock(ImageUploadCloudinaryService::class);
        $mockCloudinary->shouldReceive('uploadFile')
            ->once()
            ->andReturn('https://res.cloudinary.com/demo/image/upload/sample.jpg');

        $this->app->instance(ImageUploadCloudinaryService::class, $mockCloudinary);

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
        $this->assertEquals('https://res.cloudinary.com/demo/image/upload/sample.jpg', $responseData['data']['file_url']);
    }

    /** @test */
    public function it_can_send_a_pdf_attachment()
    {
        $mockCloudinary = Mockery::mock(ImageUploadCloudinaryService::class);
        $mockCloudinary->shouldReceive('uploadFile')
            ->once()
            ->andReturn('https://res.cloudinary.com/demo/raw/upload/test.pdf');

        $this->app->instance(ImageUploadCloudinaryService::class, $mockCloudinary);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user1, 'api')
            ->postJson("/api/v1/messages/{$this->conversation->id}/send-attachment", [
                'file' => $file
            ]);

        $response->assertStatus(201);

        $responseData = $response->json('data');
        $this->assertEquals('test.pdf', $responseData['data']['file_name']);
        $this->assertEquals('https://res.cloudinary.com/demo/raw/upload/test.pdf', $responseData['data']['file_url']);
    }
}
