<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\OTPMail;
use App\Http\Controllers\Request;
use App\Mail\OTPMail as MailOTP;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;

class ForgetPasswordController
{
    public function forgetPassword(HttpRequest $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'Email not found. Please create an account.'], 404);
        }

        // Rate limiting: Prevent multiple OTP requests within a short time
        if ($user->otp && $user->otp_expires_at > now()) {
            return response()->json(['error' => 'Please wait before requesting another OTP.'], 429);
        }

        $otp = random_int(100000, 999999);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
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

        if ($user->otp !== $request->otp) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }

        if ($user->otp_expires_at < now()) {
            return response()->json(['error' => 'OTP has expired. Please request a new one.'], 400);
        }

        Log::info('OTP verified for email: ' . $user->email);
        return response()->json(['message' => 'OTP verified successfully.'], 200);
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

        if ($user->otp !== $request->otp) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }

        if ($user->otp_expires_at < now()) {
            return response()->json(['error' => 'OTP has expired. Please request a new one.'], 400);
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
