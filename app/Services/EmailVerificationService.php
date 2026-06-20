<?php

namespace App\Services;

use App\Mail\VerifiedSuccessfullyMail;
use App\Mail\VerifyOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EmailVerificationService
{
    // Cache key prefix for OTP
    private const CACHE_OTP_PREFIX = 'email_otp:';
    private const CACHE_RATE_LIMIT_PREFIX = 'email_otp_rate_limit:';
    private const OTP_EXPIRY_MINUTES = 10;
    private const RATE_LIMIT_MINUTES = 1;
    private const USER_TABLE = 'users';

    /**
     * Verify email OTP and update user
     *
     * @param string $email
     * @param string $otp
     * @return array
     */
    public function verifyEmailOtp(string $email, string $otp): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid email or verification code',
                'status' => 400,
            ];
        }

        // Get OTP from cache
        $cachedOtp = Cache::get(self::CACHE_OTP_PREFIX . $user->id);

        if (!$cachedOtp || $cachedOtp !== $otp) {
            return [
                'success' => false,
                'message' => 'Invalid email or verification code',
                'status' => 400,
            ];
        }

        // Verify OTP and update user
        $user->update($this->buildOtpUpdatePayload(null, verified: true));

        Cache::forget(self::CACHE_OTP_PREFIX . $user->id);

        // Send verification success email
        try {
            Mail::to($user->email)->send(new VerifiedSuccessfullyMail($user));
        } catch (\Exception $e) {
            Log::error('Failed to send verification email for user ID: ' . $user->id, ['error' => $e->getMessage()]);
        }

        Log::info('Email verified successfully for user ID: ' . $user->id);

        return [
            'success' => true,
            'message' => 'Email verified successfully',
            'status' => 200,
        ];
    }

    /**
     * Send email OTP to user
     *
     * @param string $email
     * @return array
     */
    public function sendEmailOtp(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email not found',
                'status' => 404,
            ];
        }

        if ($user->email_verified_at) {
            return [
                'success' => false,
                'message' => 'Email is already verified',
                'status' => 400,
            ];
        }

        // Check rate limit
        $rateLimitKey = self::CACHE_RATE_LIMIT_PREFIX . $user->id;
        if (Cache::has($rateLimitKey)) {
            return [
                'success' => false,
                'message' => 'Please wait before requesting another OTP',
                'status' => 429,
            ];
        }

        // Generate OTP
        $otp = $this->generateOtp();

        // Store OTP in cache with expiry
        Cache::put(
            self::CACHE_OTP_PREFIX . $user->id,
            $otp,
            now()->addMinutes(self::OTP_EXPIRY_MINUTES)
        );

        // Set rate limit cache
        Cache::put(
            $rateLimitKey,
            true,
            now()->addMinutes(self::RATE_LIMIT_MINUTES)
        );

        // Update user with OTP and expiry (for backup/redundancy)
        $user->update($this->buildOtpUpdatePayload($otp));

        // Send OTP email
        try {
            Mail::to($user->email)->send(new VerifyOtpMail($otp));
            Log::notice('Verification code sent to email: ' . $user->email);
        } catch (\Exception $e) {
            Log::error('Failed to send verification OTP to email: ' . $user->email, ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to send verification code. Please try again.',
                'status' => 500,
            ];
        }

        return [
            'success' => true,
            'message' => 'Verification code sent successfully',
            'status' => 200,
        ];
    }

    /**
     * Check if user email is verified
     *
     * @param User $user
     * @return array
     */
    public function isEmailVerified(User $user): array
    {
        if ($user->email_verified_at) {
            return [
                'success' => true,
                'message' => 'Email is verified for user: ' . $user->name,
                'status' => 200,
                'verified' => true,
            ];
        }

        return [
            'success' => false,
            'message' => 'Email is not verified. Please complete the verification process.',
            'status' => 400,
            'verified' => false,
        ];
    }

    /**
     * Get OTP expiry time in minutes
     *
     * @param int $userId
     * @return int|null
     */
    public function getOtpExpiryTime(int $userId): ?int
    {
        $ttl = Cache::getStore()->connection()->ttl(self::CACHE_OTP_PREFIX . $userId);
        return $ttl > 0 ? ceil($ttl / 60) : null;
    }

    /**
     * Clear OTP from cache
     *
     * @param int $userId
     * @return void
     */
    public function clearOtp(int $userId): void
    {
        Cache::forget(self::CACHE_OTP_PREFIX . $userId);
        Cache::forget(self::CACHE_RATE_LIMIT_PREFIX . $userId);
    }

    /**
     * Generate random 6-digit OTP
     *
     * @return string
     */
    private function generateOtp(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Build the update payload for OTP fields based on the current schema.
     *
     * @param string|null $otp
     * @param bool $verified
     * @return array<string, mixed>
     */
    private function buildOtpUpdatePayload(?string $otp, bool $verified = false): array
    {
        $payload = [
            'otp' => $otp,
        ];

        if ($verified) {
            $payload['email_verified_at'] = now();
        }

        $expiresAt = $verified ? null : now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        if (Schema::hasColumn(self::USER_TABLE, 'two_factor_expires_at')) {
            $payload['two_factor_expires_at'] = $expiresAt;
        }

        if (Schema::hasColumn(self::USER_TABLE, 'otp_expires_at')) {
            $payload['otp_expires_at'] = $expiresAt;
        }

        return $payload;
    }
}

