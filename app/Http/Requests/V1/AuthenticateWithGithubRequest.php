<?php

namespace App\Http\Requests\V1;

use App\Models\User;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticateWithGithubRequest extends FormRequest
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

    /**
     * Exchange the GitHub OAuth code or access token for a user,
     * then upsert a local User record.
     *
     * The mobile client sends the OAuth access_token directly
     * (obtained via GitHub's device/PKCE flow on the device).
     */
    public function getUserFromGithub(): User
    {
        try {
            // Fetch GitHub user profile
            $profileResponse = Http::acceptJson()
                ->withToken($this->token)
                ->get('https://api.github.com/user')
                ->throw();

            $githubUser = $profileResponse->json();

            if (empty($githubUser['id'])) {
                throw new Exception('Invalid GitHub user data');
            }

            // GitHub may not expose the email in the profile – fetch it separately
            $email = $githubUser['email'] ?? null;

            if (empty($email)) {
                $emailsResponse = Http::acceptJson()
                    ->withToken($this->token)
                    ->get('https://api.github.com/user/emails')
                    ->throw();

                $emails = $emailsResponse->json();

                // Prefer primary + verified email
                foreach ($emails as $entry) {
                    if (!empty($entry['primary']) && !empty($entry['verified'])) {
                        $email = $entry['email'];
                        break;
                    }
                }

                // Fall back to any verified email
                if (empty($email)) {
                    foreach ($emails as $entry) {
                        if (!empty($entry['verified'])) {
                            $email = $entry['email'];
                            break;
                        }
                    }
                }
            }

            if (empty($email)) {
                throw new Exception('Unable to retrieve a verified email address from GitHub.');
            }

            // Check if an account exists with this email but was not created via GitHub
            $existingUser = User::where('email', $email)->first();

            if ($existingUser && empty($existingUser->provider_id)) {
                throw ValidationException::withMessages([
                    'email' => 'An account with this email already exists. Please sign in with your original method.',
                ]);
            }

            $name     = $githubUser['name'] ?? $githubUser['login'] ?? Str::before($email, '@');
            $avatar   = $githubUser['avatar_url'] ?? null;
            $nickname = $githubUser['login'] ?? null;

            if (!$existingUser) {
                $username = $this->generateUniqueUsername($nickname ?? Str::before($email, '@'));

                $user = User::create([
                    'name'              => $name,
                    'username'          => $username,
                    'email'             => $email,
                    'github_username'   => $nickname,
                    'provider_id'       => (string) $githubUser['id'],
                    'avatar_url'        => $avatar,
                    'email_verified_at' => now(),
                    'password'          => bcrypt(Str::random(16)),
                    'role'              => 'user',
                ]);

                Log::info('New user created via GitHub', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                ]);
            } else {
                $updateData = [];

                if (empty($existingUser->name) || $existingUser->name === Str::before($existingUser->email, '@')) {
                    $updateData['name'] = $name;
                }

                if (empty($existingUser->avatar_url) && $avatar) {
                    $updateData['avatar_url'] = $avatar;
                }

                if (empty($existingUser->github_username) && $nickname) {
                    $updateData['github_username'] = $nickname;
                }

                if (!empty($updateData)) {
                    $existingUser->update($updateData);
                }

                $user = $existingUser;

                Log::info('Existing user logged in via GitHub', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                ]);
            }

            return $user;
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('GitHub authentication error', ['message' => $e->getMessage()]);
            throw new Exception('GitHub authentication failed: ' . $e->getMessage());
        }
    }

    private function generateUniqueUsername(string $base): string
    {
        $base = Str::slug($base, '_');

        do {
            $username = $base . '_' . random_int(100000, 999999);
        } while (User::where('username', $username)->exists());

        return $username;
    }
}

