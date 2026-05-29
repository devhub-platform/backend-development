<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ProfileRequests\AddAltEmailRequest;
use App\Http\Requests\ProfileRequests\VerifyAltEmailRequest;
use App\Http\Resources\UserResource;
use App\Mail\VerifyAltEmailMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VerifyAltEmailController
{
    private const CACHE_ALT_EMAIL_OTP_PREFIX = 'alt_email_otp:';
    private const CACHE_ALT_EMAIL_RATE_LIMIT_PREFIX = 'alt_email_rate_limit:';
    private const ALT_EMAIL_OTP_EXPIRY_MINUTES = 10;
    private const ALT_EMAIL_RATE_LIMIT_MINUTES = 1;

    public function addAltEmail(AddAltEmailRequest $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            if($user->alt_email && $user->alt_email_verified_at) {
                return response()->json([
                    'message' => 'You already have a verified alternative email. Please remove it before adding a new one.',
                ], 400);
            }

            if($user->alt_email && !$user->alt_email_verified_at) {
                return response()->json([
                    'message' => 'You have an alternative email pending verification. Please verify it or remove it before adding a new one.',
                ], 400);
            }


            $altEmail = $request->validated()['alt_email'];

            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = Carbon::now()->addMinutes(self::ALT_EMAIL_OTP_EXPIRY_MINUTES);

            // Store OTP in cache
            Cache::put(
                self::CACHE_ALT_EMAIL_OTP_PREFIX . $user->id,
                $otp,
                $expiresAt
            );

            $user->update([
                'alt_email' => $altEmail,
                'alt_email_verified_at' => null,
                'alt_email_otp' => $otp,
                'alt_email_otp_expires_at' => $expiresAt,
            ]);

            Mail::to($altEmail)->send(new VerifyAltEmailMail($otp, $user->name));

            Log::info("Alternative email OTP sent to: {$altEmail} for user: {$user->email}");

            return response()->json([
                'message' => 'Verification code sent to your alternative email address. Please verify within 10 minutes.',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Add alternative email failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to add alternative email. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyAltEmail(VerifyAltEmailRequest $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            if (!$user->alt_email) {
                return response()->json([
                    'message' => 'No alternative email pending verification.',
                ], 400);
            }

            if ($user->alt_email_verified_at) {
                return response()->json([
                    'message' => 'Alternative email is already verified.',
                ], 400);
            }

            $cachedOtp = Cache::get(self::CACHE_ALT_EMAIL_OTP_PREFIX . $user->id);

            if (!$cachedOtp) {
                if (!$user->alt_email_otp_expires_at || Carbon::now()->isAfter($user->alt_email_otp_expires_at)) {
                    return response()->json([
                        'message' => 'Verification code has expired. Please request a new one.',
                    ], 400);
                }
                $cachedOtp = $user->alt_email_otp;
            }

            if ($cachedOtp !== $request->otp) {
                Log::warning("Invalid OTP attempt for alt email verification - user: {$user->email}");
                return response()->json([
                    'message' => 'Invalid verification code.',
                ], 400);
            }

            // Clear OTP from cache
            Cache::forget(self::CACHE_ALT_EMAIL_OTP_PREFIX . $user->id);

            $user->update([
                'alt_email_verified_at' => Carbon::now(),
                'alt_email_otp' => null,
                'alt_email_otp_expires_at' => null,
            ]);

            Log::info("Alternative email verified successfully for user: {$user->email}");

            return response()->json([
                'message' => 'Alternative email verified successfully.',
                'data' => new UserResource($user->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Alt email verification failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to verify alternative email. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resendAltEmailOtp()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            if (!$user->alt_email) {
                return response()->json([
                    'message' => 'No alternative email to verify. Please add one first.',
                ], 400);
            }

            if ($user->alt_email_verified_at) {
                return response()->json([
                    'message' => 'Alternative email is already verified.',
                ], 400);
            }

            if (Cache::has(self::CACHE_ALT_EMAIL_RATE_LIMIT_PREFIX . $user->id)) {
                return response()->json([
                    'message' => 'Please wait before requesting another OTP.',
                ], 429);
            }

            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = Carbon::now()->addMinutes(self::ALT_EMAIL_OTP_EXPIRY_MINUTES);

            Cache::put(
                self::CACHE_ALT_EMAIL_OTP_PREFIX . $user->id,
                $otp,
                $expiresAt
            );

            Cache::put(
                self::CACHE_ALT_EMAIL_RATE_LIMIT_PREFIX . $user->id,
                true,
                now()->addMinutes(self::ALT_EMAIL_RATE_LIMIT_MINUTES)
            );

            $user->update([
                'alt_email_otp' => $otp,
                'alt_email_otp_expires_at' => $expiresAt,
            ]);

            Mail::to($user->alt_email)->send(new VerifyAltEmailMail($otp, $user->name));

            Log::info("Alternative email OTP resent to: {$user->alt_email} for user: {$user->email}");

            return response()->json([
                'message' => 'New verification code sent to your alternative email address.',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Resend alt email OTP failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to resend verification code. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function removeAltEmail()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            if ($user->isLoginViaAltEmail()) {
                return response()->json([
                    'message' => 'You cannot remove your alternative email while logged in via it. Please log in with your primary email address first.',
                ], 403);
            }

            if (!$user->alt_email) {
                return response()->json([
                    'message' => 'No alternative email to remove.',
                ], 400);
            }

            $altEmail = $user->alt_email;

            // Clear cache
            Cache::forget(self::CACHE_ALT_EMAIL_OTP_PREFIX . $user->id);
            Cache::forget(self::CACHE_ALT_EMAIL_RATE_LIMIT_PREFIX . $user->id);

            $user->update([
                'alt_email' => null,
                'alt_email_verified_at' => null,
                'alt_email_otp' => null,
                'alt_email_otp_expires_at' => null,
            ]);

            Log::info("Alternative email removed for user: {$user->email}, removed email: {$altEmail}");

            return response()->json([
                'message' => 'Alternative email removed successfully.',
                'data' => new UserResource($user->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Remove alt email failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to remove alternative email. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getAltEmailStatus()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            return response()->json([
                'alt_email' => $user->alt_email,
                'is_verified' => (bool)$user->alt_email_verified_at,
                'verified_at' => $user->alt_email_verified_at,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Get alt email status failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to get alternative email status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function makeAsPrimaryEmail()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            if (!$user->alt_email) {
                return response()->json([
                    'message' => 'No alternative email to make primary.',
                ], 400);
            }

            if (!$user->alt_email_verified_at) {
                return response()->json([
                    'message' => 'Alternative email must be verified before making it primary.',
                ], 400);
            }

            $oldPrimaryEmail = $user->email;
            $newPrimaryEmail = $user->alt_email;

            Cache::forget(self::CACHE_ALT_EMAIL_OTP_PREFIX . $user->id);
            Cache::forget(self::CACHE_ALT_EMAIL_RATE_LIMIT_PREFIX . $user->id);

            $user->update([
                'email' => $newPrimaryEmail,
                'alt_email' => $oldPrimaryEmail,
                'alt_email_verified_at' => Carbon::now(),
                'alt_email_otp' => null,
                'alt_email_otp_expires_at' => null,
            ]);

            Log::info("Alternative email made primary for user: {$user->email}, old primary: {$oldPrimaryEmail}");

            return response()->json([
                'message' => 'Alternative email is now your primary email address.',
                'data' => new UserResource($user->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Make alt email as primary failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to make alternative email as primary. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
