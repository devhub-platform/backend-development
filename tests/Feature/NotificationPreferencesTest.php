<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_notification_preferences_with_valid_types(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->putJson('/api/v1/notifications/preferences', [
            'preferences' => [
                'new_comment' => false,
                'mention' => false,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Notification preferences updated successfully')
            ->assertJsonPath('notification_preferences.new_comment', false)
            ->assertJsonPath('notification_preferences.mention', false);
    }

    public function test_it_rejects_unknown_notification_types_on_update(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->putJson('/api/v1/notifications/preferences', [
            'preferences' => [
                'new_comment' => false,
                'unknown_type' => true,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Invalid notification type')
            ->assertJsonPath('invalid_types.0', 'unknown_type');
    }

    public function test_it_toggles_a_valid_notification_preference(): void
    {
        $user = User::factory()->create([
            'notification_preferences' => [
                'new_reaction' => true,
            ],
        ]);

        $response = $this->actingAs($user, 'api')->patchJson('/api/v1/notifications/preferences/new_reaction/toggle');

        $response->assertStatus(200)
            ->assertJsonPath('type', 'new_reaction')
            ->assertJsonPath('enabled', false);

        $this->assertFalse($user->fresh()->getNotificationPreferences()['new_reaction']);
    }

    public function test_it_rejects_invalid_notification_type_on_toggle(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->patchJson('/api/v1/notifications/preferences/not_real/toggle');

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Invalid notification type');
    }
}

