<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
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
            ->stateless() // Use stateless to avoid session issues in API
            ->redirect() // Get the redirect response
            ->getTargetUrl(); // Extract the target URL

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

    /**
     * @param $googleUser
     * @return JsonResponse
     */
    public function extracted($googleUser): JsonResponse
    {
        $username = $googleUser->getNickname()
            ?? explode('@', $googleUser->getEmail())[0]
            . '_' . substr($googleUser->getId(), 0, 5);

        $user = User::updateOrCreate(
            [
                'provider_id' => $googleUser->getId(),
            ],
            [
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'username' => $username,
                'password' => bcrypt(str()->random(16)),
                'avatar_url' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'bio' => $googleUser->getNickname() ?? '',
            ]
        );

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

}
