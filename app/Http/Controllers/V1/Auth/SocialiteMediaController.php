<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use Google_Client;

class SocialiteMediaController
{
    public function loginGoogle(): JsonResponse
    {
        $redirectUrl = Socialite::driver('google')
            ->stateless() // Use stateless to avoid session issues in API
            ->redirect() // Get the redirect response
            ->getTargetUrl(); // Extract the target URL

        Log::info('Generated Google OAuth redirect URL: ' . $redirectUrl);
        return response()->json([
            'url' => $redirectUrl
        ]);
    }

    public function loginGoogleForMobile(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        try {
            $client = new Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($request->id_token);

            if (!$payload) {
                return response()->json([
                    'message' => 'Invalid Google token'
                ], 401);
            }

            $email = $payload['email'];
            $name = $payload['name'] ?? str()->before($email, '@');
            $avatar = $payload['picture'] ?? null;
            $googleId = $payload['sub'];

            $username = str()->before($email, '@') . '_' . strval(rand(9999, 99999));

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'username' => $username,
                    'website_url' => null,
                    'role' => 'user',
                    'bio' => null,
                    'github_username' => null,
                    'provider_id' => $googleId,
                    'password' => bcrypt(str()->random(16)),
                    'avatar_url' => $avatar,
                    'email_verified_at' => now(),
                ]
            );

            JWTAuth::factory()->setTTL(60 * 24 * 30 * 12); // Set token to expire in 30 days
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message' => 'Login successful using Google',
                'user' => new UserResource($user),
                'token' => $token
            ]);

        } catch (\Exception $e) {
            Log::error('Google mobile login failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Authentication failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 401);
        }
    }


    public function loginGithub(): JsonResponse
    {
        $redirectUrl = Socialite::driver('github')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        Log::info('Generated GitHub OAuth redirect URL: ' . $redirectUrl);
        return response()->json([
            'url' => $redirectUrl
        ]);
    }

    public function callbackGoogle(): JsonResponse
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        return $this->extracted($googleUser);
    }

    public function callbackGithub(): JsonResponse
    {
        $githubUser = Socialite::driver('github')->stateless()->user();
        return $this->extracted($githubUser);
    }

    public function extracted($mediaUser): JsonResponse
    {
        $username = str()->before($mediaUser->getEmail(), '@')
            . '_' . strval(rand(9999, 99999));

        $user = User::UpdateOrCreate(
            [
                'email' => $mediaUser->getEmail(),
            ],
            [
                'name' => $mediaUser->getName(),
                'username' => $username,
                'website_url' => null,
                'role' => 'user',
                'bio' => $mediaUser->getNickname(),
                'github_username' => $mediaUser->getNickname(),
                'provider_id' => $mediaUser->getId(),
                'password' => bcrypt(str()->random(16)),
                'avatar_url' => $mediaUser->getAvatar(),
                'email_verified_at' => now(),
            ]
        );

        JWTAuth::factory()->setTTL(60 * 24 * 7); // Set token to expire in 1 week (7 days)
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Login successful using social media',
            'user' => new UserResource($user),
            'token' => $token
        ]);
    }

}
