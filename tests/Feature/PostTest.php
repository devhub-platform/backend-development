<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/api/v1/posts');

        $response->assertStatus(200);
    }

    public function test_add_post()
    {
        $postData = Post::factory()->make()->toArray();
        $response = $this->postJson('/api/v1/posts', $postData);
        $response->assertStatus(201);
    }

}
