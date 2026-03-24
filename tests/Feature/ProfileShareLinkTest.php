<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileShareLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_profile_share_links(): void
    {
        config()->set('services.profile_share.web_base_url', 'https://devhub.app');
        config()->set('services.profile_share.deep_link_scheme', 'devhub');
        config()->set('services.profile_share.deep_link_profile_path', 'profile');

        $user = User::factory()->create([
            'username' => 'flutter_user',
            'name' => 'Flutter User',
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/profile/share-link');

        $response->assertOk()->assertJson([
            'data' => [
                'user' => [
                    'avatar_url' => $user->avatar_url,
                    'username' => 'flutter_user',
                    'name' => 'Flutter User',
                ],
                'links' => [
                    'deep_link' => 'devhub://profile/flutter_user',
                    'web_url' => 'https://devhub.app/u/flutter_user',
                    'fallback' => 'https://devhub.app/u/flutter_user',
                ],
                'share_text' => 'Check out flutter_user on DevHub: https://devhub.app/u/flutter_user',
            ],
        ]);
    }
}


