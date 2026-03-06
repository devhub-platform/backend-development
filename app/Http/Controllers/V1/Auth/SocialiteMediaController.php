<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Requests\V1\AuthenticateWithGoogleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialiteMediaController
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

    public function loginGoogleForMobile(AuthenticateWithGoogleRequest $request): JsonResponse
    {
        try {
            $user = $request->getUserFromGoogle();

            JWTAuth::factory()->setTTL(60 * 24 * 30 * 12); // 30 days
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message' => 'Google login successful with mobile',
                'user' => new UserResource($user),
                'token' => $token,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Google login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
            ], 500);
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
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            return $this->extracted($googleUser);
        } catch (ClientException $e) {
            $errorCode = $this->parseOAuthClientException($e, 'Google');
            if ($errorCode === 'invalid_grant') {
                return response()->json([
                    'success' => false,
                    'message' => 'The authorization code has expired or has already been used. Please try signing in again.',
                    'error'   => 'invalid_grant',
                ], 400);
            }
            return response()->json([
                'success' => false,
                'message' => 'Google authentication failed. Please try again.',
                'error'   => $errorCode,
            ], 400);
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during Google authentication.',
            ], 500);
        }
    }

    public function callbackGithub(): JsonResponse
    {
        try {
            $githubUser = Socialite::driver('github')->stateless()->user();
            return $this->extracted($githubUser);
        } catch (ClientException $e) {
            $errorCode = $this->parseOAuthClientException($e, 'GitHub');
            return response()->json([
                'success' => false,
                'message' => 'GitHub authentication failed. Please try again.',
                'error'   => $errorCode,
            ], 400);
        } catch (\Exception $e) {
            Log::error('GitHub OAuth callback error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during GitHub authentication.',
            ], 500);
        }
    }

    private function parseOAuthClientException(ClientException $e, string $provider): string
    {
        $response     = $e->getResponse();
        $rawBody      = $response !== null ? (string) $response->getBody() : '';
        $responseBody = $rawBody !== '' ? json_decode($rawBody, true) : null;
        $errorCode    = is_array($responseBody) ? ($responseBody['error'] ?? 'oauth_error') : 'oauth_error';

        Log::warning("{$provider} OAuth callback failed", [
            'error'             => $errorCode,
            'error_description' => is_array($responseBody) ? ($responseBody['error_description'] ?? null) : null,
            'status'            => $e->getCode(),
        ]);

        return $errorCode;
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
