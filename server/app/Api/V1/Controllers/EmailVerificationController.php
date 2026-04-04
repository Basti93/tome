<?php

namespace App\Api\V1\Controllers;

use App\User;
use App\Mail\VerifyEmail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmailVerificationController extends Controller
{
    private const VERIFICATION_TOKEN_EXPIRY_HOURS = 24;

    /**
     * Send email verification link to user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user->email_verified_at) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Email already verified.'
            ]);
        }

        // Generate verification token
        $token = Str::random(60);
        $user->email_verification_token = $token;
        $user->save();

        // Generate verification link
        $frontendUrl = config('app.vue_url', 'http://localhost:8080');
        $verificationLink = $frontendUrl . '/#/verify-email?token=' . $token;

        // Send verification email
        try {
            Mail::to($user->email)->send(new VerifyEmail($user, $verificationLink));
        } catch (\Exception $e) {
            \Log::error('Email verification send failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send verification email.'
            ], 500);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Verification email sent.'
        ]);
    }

    /**
     * Verify email with token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $user = User::where('email_verification_token', $request->input('token'))
                    ->whereNull('email_verified_at')
                    ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired verification token.'
            ], 400);
        }

        // Check if token is not too old (24 hours)
        if ($user->updated_at && Carbon::parse($user->updated_at)->addHours(self::VERIFICATION_TOKEN_EXPIRY_HOURS)->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Verification token has expired. Please request a new one.'
            ], 400);
        }

        // Mark email as verified
        $user->email_verified_at = Carbon::now();
        $user->email_verification_token = null;
        $user->save();

        return response()->json([
            'status' => 'ok',
            'message' => 'Email verified successfully.'
        ]);
    }
}
