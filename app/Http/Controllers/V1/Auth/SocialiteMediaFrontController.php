<?php

namespace App\Http\Controllers\V1\Auth;

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

    /**
     * Generate GitHub OAuth redirect URL
     */
    public function loginGithub(): JsonResponse
    {
        config([
            'services.github.client_id' => env('GITHUB_FRONTEND_CLIENT_ID'),
            'services.github.client_secret' => env('GITHUB_FRONTEND_CLIENT_SECRET'),
            'services.github.redirect' => env('GITHUB_FRONTEND_REDIRECT_URL'),
        ]);

        $redirectUrl = Socialite::driver('github')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        Log::info('Generated GitHub OAuth redirect URL: ' . $redirectUrl);

        return response()->json([
            'url' => $redirectUrl
        ]);
    }

    /**
     * Handle Google OAuth callback
     */
    public function callbackGoogle()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            return $this->handleSocialUser($googleUser);
        } catch (\Exception $e) {
            Log::error('Google callback error: ' . $e->getMessage());
            return $this->redirectWithError('google_auth_failed');
        }
    }

    /**
     * Handle GitHub OAuth callback
     */
    public function callbackGithub()
    {
        try {
            config([
                'services.github.client_id' => env('GITHUB_FRONTEND_CLIENT_ID'),
                'services.github.client_secret' => env('GITHUB_FRONTEND_CLIENT_SECRET'),
                'services.github.redirect' => env('GITHUB_FRONTEND_REDIRECT_URL'),
            ]);
            $githubUser = Socialite::driver('github')->stateless()->user();
            return $this->handleSocialUser($githubUser);
        } catch (\Exception $e) {
            Log::error('GitHub callback error: ' . $e->getMessage());
            return $this->redirectWithError('github_auth_failed');
        }
    }

    /**
     * Create or update user and issue JWT token
     */
    private function handleSocialUser($mediaUser)
    {
        if (!$mediaUser->getEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email not provided by provider'
            ]);
        }

        $username = str()->before($mediaUser->getEmail(), '@') . '_' . uniqid();

        $user = User::updateOrCreate(
            [
                'email' => $mediaUser->getEmail(),
            ],
            [
                'name' => $mediaUser->getName() ?? '',
                'username' => $username,
                'website_url' => null,
                'role' => 'user',
                'bio' => $mediaUser->getNickname() ?? '',
                'github_username' => $mediaUser->getNickname() ?? '',
                'provider_id' => $mediaUser->getId(),
                'password' => bcrypt(str()->random(16)),
                'avatar_url' => $mediaUser->getAvatar(),
                'email_verified_at' => now(),
            ]
        );

        JWTAuth::factory()->setTTL(60 * 24 * 30 * 12);
        $token = JWTAuth::fromUser($user);

        // $frontendUrl = rtrim(config('app.frontend_url'), '/')
        //     . '/auth/social-callback?token=' . urlencode($token);

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Redirect to frontend with error flag
     */
    private function redirectWithError(string $error)
    {
        // $frontendUrl = rtrim(config('app.frontend_url'), '/')
        //     . '/auth/social-callback?error=' . $error;

        // return Redirect::to($frontendUrl);
    }
}