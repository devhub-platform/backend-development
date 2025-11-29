<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\JsonResponse;
use App\Http\Controllers\RandomException;
use App\Http\Controllers\Request;
use App\Http\Requests\ResendEmailRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Mail\VerifiedSuccessfullyMail;
use App\Mail\VerifyOtpMail;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VerifyEmailController
{
    public function verifyEmailOtp(VerifyEmailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp !== $request->otp) {
            return $this->errorResponse('Invalid email or verification code', 400);
        }

        $user->update([
            'email_verified_at' => now(),
            'otp' => null,
        ]);
        Log::info('Email verified successfully for user ID: ' . $user->id);
        Mail::to($user->email)->send(new VerifiedSuccessfullyMail($user));

        return $this->successResponse('Email verified successfully');
    }

    /**
     * @throws RandomException
     */
    public function resendEmail(ResendEmailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('Email not found', 404);
        }

        $otp = random_int(1000, 9999);
        $user->update([
            'otp' => $otp,
        ]);

        Mail::to($user->email)->send(new VerifyOtpMail($otp));
        Log::notice('Verification code resent to email: ' . $user->email);
        return $this->successResponse('Verification code resent successfully');
    }

    public function isVerified()
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }
        if ($user->otp) {
            return $this->successResponse('Email is verified with user: ' . $user->name);
        }

        return $this->errorResponse('Email is not verified', 400);
    }

    public function resetEmailVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:4',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp !== $request->otp) {
            return $this->errorResponse('Invalid email or OTP', 400);
        }

        if ($user->otp_expires_at < now()) {
            return $this->errorResponse('OTP has expired', 400);
        }

        $user->update([
            'otp' => null,
            'two_factor_expires_at' => null,
        ]);

        Log::notice('Email reset successful for user ID: ' . $user->id);

        return $this->successResponse('Password reset successful');
    }

    private function errorResponse(string $message, int $status)
    {
        return response()->json(['error' => $message], $status);
    }

    private function successResponse(string $message)
    {
        return response()->json(['message' => $message], 200);
    }
}
