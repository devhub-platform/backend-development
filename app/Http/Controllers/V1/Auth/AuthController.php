<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequests\LoginRequest;
use App\Http\Requests\AuthRequests\RegisteredRequest;
use App\Http\Resources\UserResource;
use App\Mail\WelcomeEmailMail;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use OneSignal;

class AuthController extends Controller
{
    use AuthorizesRequests;

    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $remember = (bool)($request->input('remember_me') ?? false);

        JWTAuth::factory()->setTTL($remember ? 60 * 24 * 30 : 60 * 24);

        if ($token = JWTAuth::attempt(['email' => $email, 'password' => $password])) {
            return response()->json([
                'message' => 'Login successful',
                'user' => new UserResource(Auth::user()),
                'token' => $token,
                'remember_me' => $remember,
            ]);
        }

        $user = User::where('alt_email', $email)
            ->whereNotNull('alt_email_verified_at')
            ->first();

        if ($user && $this->verifyPassword($password, $user->password)) {
            $this->setLoginMethod($user, 'alt_email');
            $token = JWTAuth::fromUser($user);
            return response()->json([
                'message' => 'Login successful',
                'data' => new UserResource($user),
                'token' => $token,
                'remember_me' => $remember,
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function register(RegisteredRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['username'] = $data['username'] ?? $this->generateUniqueUsername($data['email']);
        $data['password'] = bcrypt($data['password']);

        try {
            DB::beginTransaction();

            $user = User::create($data);

            JWTAuth::factory()->setTTL(60 * 24 * 30 * 3); // 3 months
            $token = JWTAuth::fromUser($user);

            DB::commit();

            $this->sendWelcomeEmail($user);

            OneSignal::sendNotificationToUser(
                "Welcome to DevHub, {$user->name}!",
                auth()->user()->onesignal_player_id,
                'deeplink://home',
                null,
                null,
                "Explore DevHub and connect with fellow developers."
            );

            return response()->json([
                'message' => 'User registered successfully',
                'user' => new UserResource($user),
                'token' => $token,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User registration failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e,
                'errors' => ['server' => ['An unexpected error occurred. Please try again later.']]
            ], 500);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $token = JWTAuth::getToken();
            if (!$token) {
                return response()->json(['message' => 'Token not provided'], 401);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            JWTAuth::invalidate($token);
            Log::notice('User logged out', ['user' => $user->email]);

            return response()->json(['message' => "User {$user->name} successfully logged out."], 200);
        } catch (JWTException $e) {
            Log::error('Logout failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to logout, token invalid or expired',
            ], 500);
        }
    }

    public function user(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated'
                ], 401);
            }

            Log::info('Fetched user details', ['email' => $user->email]);

            return response()->json([
                'user' => new UserResource($user),
            ]);
        } catch (JWTException $e) {
            Log::error('Fetch user failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to fetch user',
            ], 500);
        }
    }

    public function refreshToken(): JsonResponse
    {
        try {
            $token = JWTAuth::getToken();
            if (!$token) {
                return response()->json(['message' => 'Token not provided'], 401);
            }
            $newToken = JWTAuth::refresh($token);
            $user = JWTAuth::setToken($newToken)->toUser();

            return response()->json([
                'message' => 'Token refreshed successfully',
                'new_token' => $newToken,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                ],
            ], 200);

        } catch (JWTException $e) {
            Log::error('Token refresh failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to refresh token',
            ], 401);
        }
    }

    private function generateUniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '_');

        do {
            $username = $base . '_' . random_int(100000, 999999);
        } while (User::where('username', $username)->exists());
        return $username;
    }

    private function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    private function setLoginMethod(User $user, string $method): void
    {
        $user->setAttribute('login_method', $method);
    }

    private function sendWelcomeEmail(User $user): void
    {
        try {
            Mail::to($user->email)->send(new WelcomeEmailMail($user));
        } catch (\Throwable $e) {
            Log::warning('Failed to send welcome email', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
