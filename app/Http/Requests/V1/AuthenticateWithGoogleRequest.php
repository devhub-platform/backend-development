<?php

namespace App\Http\Requests\V1;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthenticateWithGoogleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
        ];
    }

    public function getUserFromGoogle(): User
    {
        try {
            $response = Http::acceptJson()
                ->withToken($this->token)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo')
                ->throw();

            $googleUser = $response->json();

            if (!isset($googleUser['email']) || !isset($googleUser['sub'])) {
                throw new Exception('Invalid Google user data');
            }

            $user = User::where('email', $googleUser['email'])->first();

            // Block login if the email is registered without Google
            if ($user && empty($user->provider_id)) {
                throw ValidationException::withMessages([
                    'email' => 'An account with this email already exists. Please sign in with your original method.'
                ]);
            }

            $name = trim(
                ($googleUser['given_name'] ?? '') . ' ' . ($googleUser['family_name'] ?? '')
            ) ?: Str::before($googleUser['email'], '@');

            $avatar = $googleUser['picture'] ?? null;

            if (!$user) {
                $username = $this->generateUniqueUsername(Str::before($googleUser['email'], '@'));

                $user = User::create([
                    'name' => $name,
                    'username' => $username,
                    'email' => $googleUser['email'],
                    'provider_id' => $googleUser['sub'],
                    'avatar_url' => $avatar,
                    'email_verified_at' => now(),
                    'password' => bcrypt(Str::random(16)),
                ]);

                Log::info('New user created via Google', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            } else {
                $updateData = [];

                if (empty($user->name) || $user->name === Str::before($user->email, '@')) {
                    $updateData['name'] = $name;
                }
                if (empty($user->avatar_url) && $avatar) {
                    $updateData['avatar_url'] = $avatar;
                }
                if (empty($user->provider_id)) {
                    $updateData['provider_id'] = $googleUser['sub'];
                }
                if (!empty($updateData)) {
                    $user->update($updateData);
                }

                Log::info('Existing user logged in via Google', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            return $user;

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Google authentication error: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'token' => 'Invalid Google access token or authentication failed'
            ]);
        }
    }

    private function generateUniqueUsername(string $baseUsername): string
    {
        $baseUsername = substr(Str::slug($baseUsername, ''), 0, 20);

        if (!User::where('username', $baseUsername)->exists()) {
            return $baseUsername;
        }

        $counter = 1;
        do {
            $username = $baseUsername . $counter;
            $counter++;
        } while (User::where('username', $username)->exists() && $counter < 1000);

        return $counter >= 1000 ? $baseUsername . Str::random(4) : $username;
    }
}
