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
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'Email not found or create account!'], 404);
        }

        $otp = rand(100000, 999999); // Generate a 6-digit OTP
        $otp = strval($otp); // Convert to string
        $user->update(['otp' => $otp]);
        $subject = 'OTP for Password Reset';

        Mail::to($user->email)->send(new MailOTP($otp));
        Log::notice('OTP sent to email: ' . $user->email);
        return response()->json(['message' => 'OTP sent to your email'], 200);
    }

    public function verifyOtp(HttpRequest $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'Email not found'], 404);
        }

        if ($user->otp != $request->otp) {
            return response()->json(['error' => 'Invalid OTP'], 400);
        }

        Log::info('OTP verified for email: ' . $user->email);
        return response()->json(['message' => 'OTP verified successfully'], 200);
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
            return response()->json(['error' => 'Email not found'], 404);
        }

        if ($user->otp != $request->otp) {
            return response()->json(['error' => 'Invalid OTP'], 400);
        }

        $user->password = bcrypt($request->password);
        $user->otp = null; // Clear the OTP
        $user->save();

        Log::alert('Password reset for email: ' . $user->email);
        return response()->json(['message' => 'Password reset successful login with new password!'], 200);
    }
}
