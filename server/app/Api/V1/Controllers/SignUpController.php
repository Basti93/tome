<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\SignUpRequest;
use App\Http\Controllers\Controller;
use App\Mail\Welcome;
use App\Mail\VerifyEmail;
use App\User;
use Config;
use Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Illuminate\Support\Str;
use DateTime;
use App\Services\AuthLogService;

class SignUpController extends Controller
{
    public function signUp(SignUpRequest $request, JWTAuth $JWTAuth)
    {
        $user = new User($request->all());

        // Generate email verification token
        $user->email_verification_token = Str::random(60);

        if (!$user->save()) {
            throw new HttpException(500);
        }

        AuthLogService::signUp($request->input('email'), $request);
        $this->sendVerificationEmail($user);

        return response()->json([
            'status' => 'ok',
            'message' => 'Registration successful. Please check your email to verify your account.'
        ], 201);
    }

    public function sendVerificationEmail($user)
    {
        $frontendUrl = config('app.vue_url', 'http://localhost:8080');
        $verificationLink = $frontendUrl . '/verify-email?token=' . $user->email_verification_token;
        Mail::to($user)->send(new VerifyEmail($user, $verificationLink));
    }
}
