<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ProfileRequests\AddAltEmailRequest;
use App\Http\Requests\ProfileRequests\VerifyAltEmailRequest;
use App\Http\Resources\UserResource;
use App\Mail\VerifyAltEmailMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VerifyAltEmailController
{
    public function addAltEmail(AddAltEmailRequest $request)
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
                    'message' => 'You cannot add a new alternative email while logged in via your alternative email. Please log in with your primary email address first.',
                ], 403);
            }

            $altEmail = $request->validated()['alt_email'];

            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = Carbon::now()->addMinutes(10);

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

            if (!$user->alt_email_otp_expires_at || Carbon::now()->isAfter($user->alt_email_otp_expires_at)) {
                return response()->json([
                    'message' => 'Verification code has expired. Please request a new one.',
                ], 400);
            }

            if ($user->alt_email_otp !== $request->otp) {
                Log::warning("Invalid OTP attempt for alt email verification - user: {$user->email}");
                return response()->json([
                    'message' => 'Invalid verification code.',
                ], 400);
            }

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

    /**
     * Resend OTP to alternative email
     */
    public function resendAltEmailOtp()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            // Check if alt email exists
            if (!$user->alt_email) {
                return response()->json([
                    'message' => 'No alternative email to verify. Please add one first.',
                ], 400);
            }

            // Check if already verified
            if ($user->alt_email_verified_at) {
                return response()->json([
                    'message' => 'Alternative email is already verified.',
                ], 400);
            }

            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = Carbon::now()->addMinutes(10);

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
}
