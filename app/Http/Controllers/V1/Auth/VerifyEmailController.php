<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Requests\EmailVerificationReqests\ResendEmailRequest;
use App\Http\Requests\EmailVerificationReqests\VerifyEmailRequest;
use App\Services\EmailVerificationService;

class VerifyEmailController
{
    public function __construct(private EmailVerificationService $emailVerificationService)
    {}

    public function verifyEmailOtp(VerifyEmailRequest $request)
    {
        $result = $this->emailVerificationService->verifyEmailOtp(
            $request->email,
            $request->otp
        );

        return response()->json(
            ['message' => $result['message']],
            $result['status']
        );
    }

    public function sendEmailOTP(ResendEmailRequest $request)
    {
        $result = $this->emailVerificationService->sendEmailOtp($request->email);

        return response()->json(
            ['message' => $result['message']],
            $result['status']
        );
    }

    public function isVerified()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(
                ['error' => 'Unauthenticated. Please log in to check verification status.'],
                401
            );
        }

        $result = $this->emailVerificationService->isEmailVerified($user);

        return response()->json(
            ['message' => $result['message']],
            $result['status']
        );
    }
}
