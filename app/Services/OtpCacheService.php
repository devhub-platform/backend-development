<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class OtpCacheService
{
    /**
     * Cache key prefixes
     */
    private const EMAIL_OTP_PREFIX = 'email_otp:';
    private const EMAIL_RATE_LIMIT_PREFIX = 'email_rate_limit:';
    private const ALT_EMAIL_OTP_PREFIX = 'alt_email_otp:';
    private const ALT_EMAIL_RATE_LIMIT_PREFIX = 'alt_email_rate_limit:';
    private const PASSWORD_RESET_OTP_PREFIX = 'password_reset_otp:';
    private const PASSWORD_RESET_RATE_LIMIT_PREFIX = 'password_reset_rate_limit:';

    /**
     * Cache expiry times (in minutes)
     */
    private const OTP_EXPIRY_MINUTES = 10;
    private const RATE_LIMIT_MINUTES = 1;

    /**
     * Store email verification OTP in cache
     *
     * @param int $userId
     * @param string $otp
     * @return void
     */
    public function storeEmailOtp(int $userId, string $otp): void
    {
        Cache::put(
            self::EMAIL_OTP_PREFIX . $userId,
            $otp,
            now()->addMinutes(self::OTP_EXPIRY_MINUTES)
        );
    }

    /**
     * Get email verification OTP from cache
     *
     * @param int $userId
     * @return string|null
     */
    public function getEmailOtp(int $userId): ?string
    {
        return Cache::get(self::EMAIL_OTP_PREFIX . $userId);
    }

    /**
     * Verify email OTP
     *
     * @param int $userId
     * @param string $otp
     * @return bool
     */
    public function verifyEmailOtp(int $userId, string $otp): bool
    {
        $cachedOtp = $this->getEmailOtp($userId);
        return $cachedOtp !== null && $cachedOtp === $otp;
    }

    /**
     * Clear email OTP from cache
     *
     * @param int $userId
     * @return void
     */
    public function clearEmailOtp(int $userId): void
    {
        Cache::forget(self::EMAIL_OTP_PREFIX . $userId);
    }

    /**
     * Set email OTP rate limit
     *
     * @param int $userId
     * @return void
     */
    public function setEmailRateLimit(int $userId): void
    {
        Cache::put(
            self::EMAIL_RATE_LIMIT_PREFIX . $userId,
            true,
            now()->addMinutes(self::RATE_LIMIT_MINUTES)
        );
    }

    /**
     * Check if email OTP request is rate limited
     *
     * @param int $userId
     * @return bool
     */
    public function isEmailRateLimited(int $userId): bool
    {
        return Cache::has(self::EMAIL_RATE_LIMIT_PREFIX . $userId);
    }

    /**
     * Store alternative email OTP in cache
     *
     * @param int $userId
     * @param string $otp
     * @return void
     */
    public function storeAltEmailOtp(int $userId, string $otp): void
    {
        Cache::put(
            self::ALT_EMAIL_OTP_PREFIX . $userId,
            $otp,
            now()->addMinutes(self::OTP_EXPIRY_MINUTES)
        );
    }

    /**
     * Get alternative email OTP from cache
     *
     * @param int $userId
     * @return string|null
     */
    public function getAltEmailOtp(int $userId): ?string
    {
        return Cache::get(self::ALT_EMAIL_OTP_PREFIX . $userId);
    }

    /**
     * Verify alternative email OTP
     *
     * @param int $userId
     * @param string $otp
     * @return bool
     */
    public function verifyAltEmailOtp(int $userId, string $otp): bool
    {
        $cachedOtp = $this->getAltEmailOtp($userId);
        return $cachedOtp !== null && $cachedOtp === $otp;
    }

    /**
     * Clear alternative email OTP from cache
     *
     * @param int $userId
     * @return void
     */
    public function clearAltEmailOtp(int $userId): void
    {
        Cache::forget(self::ALT_EMAIL_OTP_PREFIX . $userId);
    }

    /**
     * Set alternative email OTP rate limit
     *
     * @param int $userId
     * @return void
     */
    public function setAltEmailRateLimit(int $userId): void
    {
        Cache::put(
            self::ALT_EMAIL_RATE_LIMIT_PREFIX . $userId,
            true,
            now()->addMinutes(self::RATE_LIMIT_MINUTES)
        );
    }

    /**
     * Check if alternative email OTP request is rate limited
     *
     * @param int $userId
     * @return bool
     */
    public function isAltEmailRateLimited(int $userId): bool
    {
        return Cache::has(self::ALT_EMAIL_RATE_LIMIT_PREFIX . $userId);
    }

    /**
     * Store password reset OTP in cache
     *
     * @param int $userId
     * @param string $otp
     * @return void
     */
    public function storePasswordResetOtp(int $userId, string $otp): void
    {
        Cache::put(
            self::PASSWORD_RESET_OTP_PREFIX . $userId,
            $otp,
            now()->addMinutes(self::OTP_EXPIRY_MINUTES)
        );
    }

    /**
     * Get password reset OTP from cache
     *
     * @param int $userId
     * @return string|null
     */
    public function getPasswordResetOtp(int $userId): ?string
    {
        return Cache::get(self::PASSWORD_RESET_OTP_PREFIX . $userId);
    }

    /**
     * Verify password reset OTP
     *
     * @param int $userId
     * @param string $otp
     * @return bool
     */
    public function verifyPasswordResetOtp(int $userId, string $otp): bool
    {
        $cachedOtp = $this->getPasswordResetOtp($userId);
        return $cachedOtp !== null && $cachedOtp === $otp;
    }

    /**
     * Clear password reset OTP from cache
     *
     * @param int $userId
     * @return void
     */
    public function clearPasswordResetOtp(int $userId): void
    {
        Cache::forget(self::PASSWORD_RESET_OTP_PREFIX . $userId);
    }

    /**
     * Set password reset OTP rate limit
     *
     * @param int $userId
     * @return void
     */
    public function setPasswordResetRateLimit(int $userId): void
    {
        Cache::put(
            self::PASSWORD_RESET_RATE_LIMIT_PREFIX . $userId,
            true,
            now()->addMinutes(self::RATE_LIMIT_MINUTES)
        );
    }

    /**
     * Check if password reset OTP request is rate limited
     *
     * @param int $userId
     * @return bool
     */
    public function isPasswordResetRateLimited(int $userId): bool
    {
        return Cache::has(self::PASSWORD_RESET_RATE_LIMIT_PREFIX . $userId);
    }

    /**
     * Clear all OTP caches for a user
     *
     * @param int $userId
     * @return void
     */
    public function clearAllUserOtps(int $userId): void
    {
        Cache::forget(self::EMAIL_OTP_PREFIX . $userId);
        Cache::forget(self::EMAIL_RATE_LIMIT_PREFIX . $userId);
        Cache::forget(self::ALT_EMAIL_OTP_PREFIX . $userId);
        Cache::forget(self::ALT_EMAIL_RATE_LIMIT_PREFIX . $userId);
        Cache::forget(self::PASSWORD_RESET_OTP_PREFIX . $userId);
        Cache::forget(self::PASSWORD_RESET_RATE_LIMIT_PREFIX . $userId);
    }

    /**
     * Get OTP expiry minutes
     *
     * @return int
     */
    public function getOtpExpiryMinutes(): int
    {
        return self::OTP_EXPIRY_MINUTES;
    }

    /**
     * Get rate limit minutes
     *
     * @return int
     */
    public function getRateLimitMinutes(): int
    {
        return self::RATE_LIMIT_MINUTES;
    }
}

