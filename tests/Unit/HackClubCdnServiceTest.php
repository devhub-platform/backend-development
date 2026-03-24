<?php

namespace Tests\Unit;

use App\Exceptions\HackClubCdnException;
use App\Services\HackClubCdnService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HackClubCdnServiceTest extends TestCase
{
    private HackClubCdnService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.hackclub_cdn.base_url', 'https://cdn.hackclub.com/api/v4');
        config()->set('services.hackclub_cdn.token', 'sk_cdn_test_key');
        config()->set('services.hackclub_cdn.timeout', 30);
        config()->set('services.hackclub_cdn.retry_times', 1);
        config()->set('services.hackclub_cdn.retry_sleep_ms', 10);

        $this->service = new HackClubCdnService();
    }

    /** @test */
    public function it_uploads_a_file_and_returns_the_cdn_payload(): void
    {
        Http::fake(function ($request) {
            $this->assertSame('POST', $request->method());
            $this->assertSame('https://cdn.hackclub.com/api/v4/upload', $request->url());
            $this->assertTrue($request->hasHeader('Authorization'));

            return Http::response([
                'id' => '01234567-89ab-cdef-0123-456789abcdef',
                'filename' => 'avatar.jpg',
                'size' => 12345,
                'content_type' => 'image/jpeg',
                'url' => 'https://cdn.hackclub.com/01234567-89ab-cdef-0123-456789abcdef/avatar.jpg',
                'created_at' => '2026-01-29T12:00:00Z',
            ], 201);
        });

        $file = UploadedFile::fake()->image('avatar.jpg');
        $payload = $this->service->uploadFile($file);

        $this->assertSame('avatar.jpg', $payload['filename']);
        $this->assertStringContainsString('https://cdn.hackclub.com/', $payload['url']);
    }

    /** @test */
    public function it_uploads_from_url_and_sends_download_authorization_when_present(): void
    {
        Http::fake(function ($request) {
            $this->assertSame('POST', $request->method());
            $this->assertSame('https://cdn.hackclub.com/api/v4/upload_from_url', $request->url());
            $this->assertTrue($request->hasHeader('X-Download-Authorization'));

            return Http::response([
                'url' => 'https://cdn.hackclub.com/01234567-89ab-cdef-0123-456789abcdef/photo.jpg',
            ], 200);
        });

        $url = $this->service->uploadFromUrlOnly('https://example.com/photo.jpg', 'Bearer source-token');

        $this->assertStringContainsString('/photo.jpg', $url);
    }

    /** @test */
    public function it_throws_an_exception_when_quota_is_exceeded(): void
    {
        Http::fake([
            'https://cdn.hackclub.com/api/v4/me' => Http::response([
                'error' => 'Storage quota exceeded',
                'quota' => [
                    'storage_used' => 52428800,
                    'storage_limit' => 52428800,
                    'quota_tier' => 'unverified',
                    'percentage_used' => 100.0,
                ],
            ], 402),
        ]);

        $this->expectException(HackClubCdnException::class);
        $this->expectExceptionMessage('Storage quota exceeded');

        $this->service->me();
    }

    /** @test */
    public function it_requires_a_configured_token(): void
    {
        config()->set('services.hackclub_cdn.token', '');

        $this->expectException(HackClubCdnException::class);
        $this->expectExceptionMessage('token is missing');

        $this->service->me();
    }
}

