<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Services\AuthLogService;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    private const VERIFICATION_TOKEN_EXPIRY_HOURS = 24;

    /**
     * Send email verification link to user
     *
     * @return JsonResponse
     */
    public function sendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        // same answer whether the account exists, is verified already or the mail was sent (no enumeration)
        $generic = response()->json([
            'status' => 'ok',
            'message' => 'Falls ein unbestätigtes Konto mit dieser E-Mail-Adresse existiert, wurde eine E-Mail gesendet.',
        ]);
        if (! $user || $user->email_verified_at) {
            return $generic;
        }

        // Generate verification token
        $token = Str::random(60);
        $user->email_verification_token = $token;
        $user->save();

        // Generate verification link
        $frontendUrl = config('app.vue_url', 'http://localhost:8080');
        $verificationLink = $frontendUrl.'/#/verify-email?token='.$token;

        // Send verification email
        try {
            Mail::to($user->email)->send(new VerifyEmail($user, $verificationLink));
        } catch (\Exception $e) {
            \Log::error('Email verification send failed: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Die E-Mail konnte nicht gesendet werden. Bitte versuche es später erneut.',
            ], 500);
        }

        return $generic;
    }

    /**
     * Verify email with token
     *
     * @return JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = User::where('email_verification_token', $request->input('token'))
            ->whereNull('email_verified_at')
            ->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Der Bestätigungslink ist ungültig oder abgelaufen. Bitte fordere auf der Anmeldeseite einen neuen an.',
            ], 400);
        }

        // Check if token is not too old (24 hours)
        if ($user->updated_at && Carbon::parse($user->updated_at)->addHours(self::VERIFICATION_TOKEN_EXPIRY_HOURS)->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Der Bestätigungslink ist ungültig oder abgelaufen. Bitte fordere auf der Anmeldeseite einen neuen an.',
            ], 400);
        }

        // Mark email as verified
        $user->email_verified_at = Carbon::now();
        $user->email_verification_token = null;
        $user->save();

        AuthLogService::emailVerified($user->id, $request);

        return response()->json([
            'status' => 'ok',
            'message' => 'Deine E-Mail-Adresse wurde bestätigt. Du kannst dich jetzt anmelden.',
        ]);
    }
}
