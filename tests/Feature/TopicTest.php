<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicTest extends TestCase
{
    use RefreshDatabase;

    private function createTopic(array $overrides = []): Topic
    {
        return Topic::create(array_merge([
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'icon' => 'icon-tag',
            'display_order' => 1,
            'is_active' => true,
        ], $overrides));
    }

    /** @test */
    public function admin_can_create_a_new_topic(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/v1/topics', [
                'name' => 'Laravel Tips',
                'description' => 'Useful Laravel tips and tricks',
                'icon' => 'laravel',
                'display_order' => 10,
                'is_active' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Topic created successfully',
            ]);

        $this->assertDatabaseHas('topics', [
            'name' => 'Laravel Tips',
            'description' => 'Useful Laravel tips and tricks',
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_topics(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/topics', [
                'name' => 'Laravel Tips',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('topics', [
            'name' => 'Laravel Tips',
        ]);
    }

    /** @test */
    public function admin_can_delete_a_topic(): void
    {
        $admin = User::factory()->admin()->create();
        $topic = $this->createTopic(['name' => 'JavaScript']);

        $response = $this->actingAs($admin, 'api')
            ->deleteJson('/api/v1/topics/' . $topic->id);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Topic deleted successfully',
            ]);

        $this->assertDatabaseMissing('topics', [
            'id' => $topic->id,
        ]);
    }

    /** @test */
    public function non_admin_cannot_delete_topics(): void
    {
        $user = User::factory()->create();
        $topic = $this->createTopic();

        $response = $this->actingAs($user, 'api')
            ->deleteJson('/api/v1/topics/' . $topic->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('topics', [
            'id' => $topic->id,
        ]);
    }
}
