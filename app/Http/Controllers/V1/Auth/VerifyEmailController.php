<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Requests\EmailVerificationReqests\ResendEmailRequest;
use App\Http\Requests\EmailVerificationReqests\VerifyEmailRequest;
use App\Mail\VerifiedSuccessfullyMail;
use App\Mail\VerifyOtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class VerifyEmailController
{
    public function verifyEmailOtp(VerifyEmailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp !== $request->otp) {
            return $this->errorResponse('Invalid email or verification code', 400);
        }

        if ($user->two_factor_expires_at && $user->two_factor_expires_at < now()) {
            return $this->errorResponse('OTP has expired', 400);
        }

        $user->update([
            'email_verified_at' => now(),
            'otp' => null,
            'two_factor_expires_at' => null,
        ]);

        Log::info('Email verified successfully for user ID: ' . $user->id);
        Mail::to($user->email)->send(new VerifiedSuccessfullyMail($user));

        return $this->successResponse('Email verified successfully');
    }

    /**
     * @throws \Exception
     */
    public function sendEmailOTP(ResendEmailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('Email not found', 404);
        }

        if ($user->email_verified_at) {
            return $this->errorResponse('Email is already verified', 400);
        }

        $otp = random_int(100000, 999999);
        $user->update([
            'otp' => $otp,
            'two_factor_expires_at' => now()->addMinutes(1),
        ]);

        Mail::to($user->email)->send(new VerifyOtpMail($otp));
        Log::notice('Verification code resent to email: ' . $user->email);
        return $this->successResponse('Verification code sent successfully');
    }

    public function isVerified()
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }
        if ($user->email_verified_at) {
            return $this->successResponse('Email is verified with user: ' . $user->name);
        }

        return $this->errorResponse('Email is not verified', 400);
    }

//    public function resetEmailVerification(Request $request)
//    {
//        $request->validate([
//            'email' => 'required|email',
//            'otp' => 'required|digits:6',
//            'password' => 'required|string|min:8',
//        ]);
//
//        $user = User::where('email', $request->email)->first();
//
//        if (!$user || $user->otp !== $request->otp) {
//            return $this->errorResponse('Invalid email or OTP', 400);
//        }
//
//        if ($user->two_factor_expires_at && $user->two_factor_expires_at < now()) {
//            return $this->errorResponse('OTP has expired', 400);
//        }
//
//        $user->update([
//            'otp' => null,
//            'otp_expires_at' => null,
//            'two_factor_expires_at' => null,
//            'password' => Hash::make($request->password),
//        ]);
//
//        Log::notice('Email reset successful for user ID: ' . $user->id);
//
//        return $this->successResponse('Password reset successful');
//    }
//
    private function errorResponse(string $message, int $status)
    {
        return response()->json(['error' => $message], $status);
    }

    private function successResponse(string $message)
    {
        return response()->json(['message' => $message], 200);
    }
}
