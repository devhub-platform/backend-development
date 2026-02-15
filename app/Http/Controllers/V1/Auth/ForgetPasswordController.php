<?php

namespace App\Http\Controllers\V1\Auth;

use App\Mail\OTPMail as MailOTP;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class ForgetPasswordController
{
    private const OTP_EXPIRES_MINUTES = 15;
    private const OTP_COOLDOWN_MINUTES = 1;

    public function forgetPassword(HttpRequest $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'Email not found. Please create an account.'], 404);
        }

        if ($user->otp && $user->otp_expires_at?->isFuture()) {
            $cooldownUntil = $user->updated_at?->addMinutes(self::OTP_COOLDOWN_MINUTES);
            if ($cooldownUntil && $cooldownUntil->isFuture()) {
                return response()->json([
                    'error' => 'Please wait before requesting another OTP.',
                ], 429);
            }
        }

        $otp = (string) random_int(100000, 999999);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(self::OTP_EXPIRES_MINUTES),
        ]);

        Mail::to($user->email)->send(new MailOTP($otp));
        Log::notice('OTP sent to email: ' . $user->email);
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
            return response()->json(['error' => 'Email not found.'], 404);
        }

        if (!$user->otp_expires_at || $user->otp_expires_at->isPast()) {
            return response()->json(['error' => 'OTP has expired. Please request a new one.'], 400);
        }

        if ((string) $user->otp !== (string) $request->otp) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }

        // Don't clear the OTP yet - keep it for password reset
        // Just extend the expiration time to allow password reset
        $user->update([
            'otp_expires_at' => now()->addMinutes(self::OTP_EXPIRES_MINUTES),
        ]);

        Log::info('OTP verified for email: ' . $user->email);
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
            return response()->json(['error' => 'Email not found.'], 404);
        }

        if (!$user->otp_expires_at || $user->otp_expires_at->isPast()) {
            return response()->json(['error' => 'OTP has expired. Please request a new one.'], 400);
        }

        if ((string) $user->otp !== (string) $request->otp) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }


        $user->update([
            'password' => bcrypt($request->password),
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        Log::alert('Password reset for email: ' . $user->email);
        return response()->json(['message' => 'Password reset successful. Please log in with your new password.'], 200);
    }
}
