<?php

namespace App\Services;

use App\Mail\MfaCodeMail;
use App\Models\MfaCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class MfaCodeService
{
    public function sendEmailCode(User $user): MfaCode
    {
        $mfaCode = MfaCode::generateCode($user, 'email');

        try {
            Mail::to($user->email)->send(new MfaCodeMail($user, $mfaCode->code));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send MFA code', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        return $mfaCode;
    }

    public function verifyCode(User $user, string $code, string $type = 'email'): bool
    {
        $mfaCode = $user->mfaCodes()
            ->where('code', $code)
            ->where('type', $type)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (!$mfaCode) {
            return false;
        }

        return $mfaCode->verify();
    }

    public function isCodeExpired(MfaCode $mfaCode): bool
    {
        return $mfaCode->expires_at->isPast();
    }
}


