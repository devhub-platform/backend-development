<?php

namespace Tests\Unit\AI;

use App\Services\AI\HackAIEmbeddingService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class HackAIEmbeddingServiceTest extends TestCase
{
    public function test_it_returns_embeddings_sorted_by_index(): void
    {
        config()->set('services.hackai.base_url', 'https://ai.hackclub.com/proxy/v1');
        config()->set('services.hackai.token', 'sk_test_hackai');
        config()->set('services.hackai.embeddings_model', 'openai/text-embedding-3-large');
        config()->set('services.hackai.embeddings_timeout', 20);

        Http::fake(function ($request) {
            $this->assertSame('https://ai.hackclub.com/proxy/v1/embeddings', $request->url());
            $this->assertSame('POST', $request->method());
            $this->assertTrue($request->hasHeader('Authorization'));
            $this->assertSame('openai/text-embedding-3-large', $request['model']);

            return Http::response([
                'data' => [
                    ['index' => 1, 'embedding' => [0.2, 0.3]],
                    ['index' => 0, 'embedding' => [0.1, 0.4]],
                ],
            ], 200);
        });

        $service = new HackAIEmbeddingService();
        $embeddings = $service->embedBatch(['first text', 'second text']);

        $this->assertSame([[0.1, 0.4], [0.2, 0.3]], $embeddings);
    }

    public function test_it_throws_when_hackai_request_fails(): void
    {
        config()->set('services.hackai.base_url', 'https://ai.hackclub.com/proxy/v1');
        config()->set('services.hackai.token', 'sk_test_hackai');

        Http::fake([
            'https://ai.hackclub.com/proxy/v1/embeddings' => Http::response(['error' => 'rate_limit'], 429),
        ]);

        $this->expectException(RuntimeException::class);

        $service = new HackAIEmbeddingService();
        $service->embedBatch(['profile']);
    }

    public function test_it_throws_when_api_key_is_missing(): void
    {
        config()->set('services.hackai.token', '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HackAI API key is missing.');

        $service = new HackAIEmbeddingService();
        $service->embedBatch(['profile']);
    }
}

