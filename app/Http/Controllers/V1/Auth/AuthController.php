<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\V1\Controller;
use App\Http\Requests\AuthRequests\LoginRequest;
use App\Http\Requests\AuthRequests\RegisteredRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Mail\OTPMail;
use App\Mail\VerifyOtpMail;
use App\Mail\WelcomeEmailMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    use AuthorizesRequests;

    public function login(LoginRequest $request): ?JsonResponse
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                Log::error('Login attempt failed for email: ' . $request->email);

                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            $user = Auth::user();

            $token = JWTAuth::fromUser($user);
            Log::info('Login success for email: ' . $request->email);

            return response()->json([
                'user' => new UserResource($user),
                'token' => $token,
            ]);
        } catch (JWTException $e) {
            Log::error($e->getMessage());

            return response()->json(['error' => 'Could not create token',
                'message' => $e->getMessage()], 500);
        }
    }

    public function register(RegisteredRequest $request): ?JsonResponse
    {
        $data = $request->validated();
        $username = isset($data['email']) ? Str::before($data['email'], '@') : null;
        $username = $username ?? ($data['name'] ?? ('user_' . time()));

        $base = $username;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . '_' . $counter++;
        }
        $data['username'] = $username;
        $user = User::create($data);

        if (!$user) {
            Log::error('User registration failed for email: ' . $request->email);

            return response()->json(['message' => 'User registration failed'], 500);
        }
        $token = JWTAuth::fromUser($user);
        Mail::to($user->email)->send(new WelcomeEmailMail($user));
        return response()->json([
            'message' => 'User registered successfully',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function logout(): JsonResponse
    {
        try {
            $token = JWTAuth::getToken();
            $user = Auth::user()->name;
            if (!$token || !$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }
            JWTAuth::invalidate($token);
            Log::info('User logged out: ' . $user);

            return response()->json(['message' => "User $user successfully logged out."], 200);
        } catch (JWTException $e) {
            Log::error($e->getMessage());

            return response()->json([
                'error' => 'Failed to logout, token invalid or expired',
                'message' => $e->getMessage (),
                'at line' => $e->getLine(),
            ], 500);
        }
    }

    public function user(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'error' => 'User not authenticated'
                ], 401);
            }

            Log::info('Fetched user details for: ' . $user->email);

            return response()->json([
                'user' => new UserResource($user),
            ]);
        } catch (JWTException $e) {
            Log::error($e->getMessage());

            return response()->json([
                'error' => 'Failed to fetch user',
                'message' => $e->getMessage(),
                'at line' => $e->getLine(),
            ], 500);
        }
    }

    public function refreshToken(): ?JsonResponse
    {
        try {
            $token = JWTAuth::getToken();
            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }
            $newToken = JWTAuth::refresh($token);
            $user = JWTAuth::setToken($newToken)->toUser();

            return response()->json([
                'message' => 'Token refreshed successfully',
                'token' => $newToken,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
            ], 200);
        } catch (JWTException $e) {
            Log::error('Token refresh failed: ' . $e->getMessage());

            return response()->json(['error' => 'Failed to refresh token',
                'message' => $e->getMessage(), 401], 500);
        }
    }
}
