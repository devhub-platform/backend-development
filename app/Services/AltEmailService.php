<?php

namespace App\Services;

use App\Mail\VerifyAltEmailMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AltEmailService
{
    private const OTP_EXPIRY_MINUTES = 10;

    public function sendOtp(User $user, string $email): void
    {
        $otp        = $this->generateOtp();
        $expiresAt  = Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        $user->update([
            'alt_email'                => $email,
            'alt_email_verified_at'    => null,
            'alt_email_otp'            => $otp,
            'alt_email_otp_expires_at' => $expiresAt,
        ]);

        Mail::to($email)->queue(new VerifyAltEmailMail($otp, $user->name));

        Log::info("Alt email OTP sent", ['to' => $email, 'user' => $user->email]);
    }

    public function resendOtp(User $user): void
    {
        $otp        = $this->generateOtp();
        $expiresAt  = Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        $user->update([
            'alt_email_otp'            => $otp,
            'alt_email_otp_expires_at' => $expiresAt,
        ]);

        Mail::to($user->alt_email)->queue(new VerifyAltEmailMail($otp, $user->name));

        Log::info("Alt email OTP resent", ['to' => $user->alt_email, 'user' => $user->email]);
    }

    public function verify(User $user, string $otp): bool
    {
        if ($this->isOtpExpired($user)) {
            return false;
        }

        if (!hash_equals((string) $user->alt_email_otp, $otp)) {
            Log::warning("Invalid OTP attempt for alt email verification", ['user' => $user->email]);
            return false;
        }

        $user->update([
            'alt_email_verified_at'    => Carbon::now(),
            'alt_email_otp'            => null,
            'alt_email_otp_expires_at' => null,
        ]);

        Log::info("Alt email verified", ['user' => $user->email]);

        return true;
    }

    public function remove(User $user): void
    {
        $altEmail = $user->alt_email;

        $user->update([
            'alt_email'                => null,
            'alt_email_verified_at'    => null,
            'alt_email_otp'            => null,
            'alt_email_otp_expires_at' => null,
        ]);

        Log::info("Alt email removed", ['user' => $user->email, 'removed' => $altEmail]);
    }

    public function makePrimary(User $user): void
    {
        $oldPrimary = $user->email;
        $newPrimary = $user->alt_email;

        $user->update([
            'email'                    => $newPrimary,
            'alt_email'                => $oldPrimary,
            'alt_email_verified_at'    => Carbon::now(),
            'alt_email_otp'            => null,
            'alt_email_otp_expires_at' => null,
        ]);

        Log::info("Alt email promoted to primary", ['user' => $user->email, 'old_primary' => $oldPrimary]);
    }

    public function isOtpExpired(User $user): bool
    {
        return !$user->alt_email_otp_expires_at
            || Carbon::now()->isAfter($user->alt_email_otp_expires_at);
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}

