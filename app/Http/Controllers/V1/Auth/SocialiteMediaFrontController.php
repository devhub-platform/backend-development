<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Requests\V1\AuthenticateWithGithubRequest;
use App\Http\Requests\V1\AuthenticateWithGoogleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialiteMediaFrontController
{
    public function loginGoogle(): JsonResponse
    {
        $redirectUrl = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        Log::info('Generated Google OAuth redirect URL: ' . $redirectUrl);
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

    public function extracted($mediaUser)
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

        JWTAuth::factory()->setTTL(60 * 24 * 30 * 12); // 1 year
        $token = JWTAuth::fromUser($user);

//        return response()->json([
//            'message' => 'Login successful using social media',
//            'user' => new UserResource($user),
//            'token' => $token
//        ]);

        $frontendUrl = config('app.frontend_url') . '/auth/social-callback?token=' . $token;
        return Redirect::to($frontendUrl);
    }

}
