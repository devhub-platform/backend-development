<?php

namespace App\Http\Controllers\V1\Auth;

use App\Mail\OTPMail as MailOTP;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class ForgetPasswordController
{
    private const OTP_EXPIRES_MINUTES = 15;
    private const OTP_COOLDOWN_MINUTES = 1;
    private const OTP_CACHE_PREFIX = 'password_reset:otp:';
    private const OTP_COOLDOWN_PREFIX = 'password_reset:otp_cooldown:';

    public function forgetPassword(HttpRequest $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $user = User::where('alt_email', $request->email)
                ->whereNotNull('alt_email_verified_at')
                ->first();
        }

        if (!$user) {
            return response()->json(['error' => 'Email not found. Please create an account.'], 404);
        }

        $cache = Cache::store('redis');

        if ($cache->has($this->cooldownKey($user))) {
            return response()->json([
                'error' => 'Please wait before requesting another OTP.',
            ], 429);
        }

        $otp = (string) random_int(100000, 999999);
        $cache->put($this->otpKey($user), $otp, now()->addMinutes(self::OTP_EXPIRES_MINUTES));
        $cache->put($this->cooldownKey($user), true, now()->addMinutes(self::OTP_COOLDOWN_MINUTES));

        Mail::to($request->email)->send(new MailOTP($otp));
        Log::notice('OTP sent to email: ' . $request->email);
        return response()->json(['message' => 'OTP sent to your email.'], 200);
    }

    public function verifyOtp(HttpRequest $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $user = User::where('alt_email', $request->email)
                ->whereNotNull('alt_email_verified_at')
                ->first();
        }

        if (!$user) {
            return response()->json(['error' => 'Email not found.'], 404);
        }

        $cachedOtp = Cache::store('redis')->get($this->otpKey($user));

        if (!$cachedOtp) {
            return response()->json(['error' => 'OTP has expired. Please request a new one.'], 400);
        }

        if (!hash_equals((string) $cachedOtp, (string) $request->otp)) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }

        Cache::store('redis')->put($this->otpKey($user), (string) $cachedOtp, now()->addMinutes(self::OTP_EXPIRES_MINUTES));

        Log::info('OTP verified for email: ' . $request->email);
        return response()->json(['message' => 'OTP verified successfully. You can now reset your password.'], 200);
    }

    public function resetPassword(HttpRequest $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $user = User::where('alt_email', $request->email)
                ->whereNotNull('alt_email_verified_at')
                ->first();
        }

        if (!$user) {
            return response()->json(['error' => 'Email not found.'], 404);
        }

        $cachedOtp = Cache::store('redis')->get($this->otpKey($user));

        if (!$cachedOtp) {
            return response()->json(['error' => 'OTP has expired. Please request a new one.'], 400);
        }

        if (!hash_equals((string) $cachedOtp, (string) $request->otp)) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }

        $user->update([
            'password' => bcrypt($request->password),
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        Cache::store('redis')->forget($this->otpKey($user));
        Cache::store('redis')->forget($this->cooldownKey($user));

        Log::alert('Password reset for email: ' . $request->email);
        return response()->json(['message' => 'Password reset successful. Please log in with your new password.'], 200);
    }

    private function otpKey(User $user): string
    {
        return self::OTP_CACHE_PREFIX . $user->id;
    }

    private function cooldownKey(User $user): string
    {
        return self::OTP_COOLDOWN_PREFIX . $user->id;
    }
}
