<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialiteMediaController
{
    public function login(): JsonResponse
    {
        $redirectUrl = Socialite::driver('google')
            ->stateless() // Use stateless to avoid session issues in API
            ->redirect() // Get the redirect response
            ->getTargetUrl(); // Extract the target URL

        return response()->json([
            'url' => $redirectUrl
        ]);
    }

    public function loginGithub(): JsonResponse
    {
        $redirectUrl = Socialite::driver('github')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $redirectUrl
        ]);
    }

    public function callback(): JsonResponse
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        return $this->extracted($googleUser);
    }

    public function callbackGithub(): JsonResponse
    {
        $githubUser = Socialite::driver('github')->stateless()->user();

        return $this->extracted($githubUser);
    }
    public function callbackMicrosoft(): JsonResponse
    {
        $githubUser = Socialite::driver('github')->stateless()->user();

        return $this->extracted($githubUser);
    }

    /**
     * @param $mediaUser
     * @return JsonResponse
     */
    public function extracted($mediaUser): JsonResponse
    {
        $username = $mediaUser->getNickname()
            ?? explode('@', $mediaUser->getEmail())[0]
            . '_' . substr($mediaUser->getId(), 0, 5);

        $user = User::UpdateOrCreate(
            [
                'email' => $mediaUser->getEmail(),
            ],
            [
                'name' => $mediaUser->getName(),
                'username' => $username,
                'role' => 'user',
                'provider_id' => $mediaUser->getId(),
                'password' => bcrypt(str()->random(16)),
                'avatar_url' => $mediaUser->getAvatar(),
                'email_verified_at' => now(),
            ]
        );

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Login successful using social media',
            'user' => new UserResource($user),
            'token' => $token
        ]);
    }

}
