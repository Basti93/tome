<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use App\Services\AuthLogService;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoginController extends Controller
{
    private const MAX_FAILED_LOGIN_ATTEMPTS = 5;

    private const LOCKOUT_DURATION_MINUTES = 15;

    /**
     * Log the user in
     *
     * @return JsonResponse
     */
    public function login(LoginRequest $request, JWTAuth $JWTAuth)
    {
        $credentials = $request->only(['email', 'password']);
        $user = User::where('email', $request->input('email'))->first();

        if ($user && $user->locked_until && Carbon::parse($user->locked_until)->isFuture()) {
            AuthLogService::loginFailed($request->input('email'), 'Account locked', $request);

            return $this->lockedResponse();
        }

        try {
            $token = Auth::guard('api')->attempt($credentials);

            if (! $token || ! Auth::user()) {
                if ($user) {
                    $locked = $this->incrementFailedLoginAttempts($user);

                    if ($locked) {
                        AuthLogService::loginFailed($request->input('email'), 'Too many failed attempts', $request);

                        return $this->lockedResponse();
                    }
                }

                AuthLogService::loginFailed($request->input('email'), 'Invalid credentials', $request);

                return response()->json([
                    'status' => 'error',
                    'message' => 'E-Mail oder Passwort ist falsch.',
                    'code' => 'invalid_credentials',
                ], 403);
            }

        } catch (JWTException $e) {
            throw new HttpException(500);
        }

        // Check if email is verified
        if ($user && ! $user->email_verified_at) {
            AuthLogService::loginFailed($request->input('email'), 'Email not verified', $request);

            return response()->json([
                'status' => 'error',
                'message' => 'Bitte bestätige zuerst deine E-Mail-Adresse.',
                'code' => 'email_not_verified',
            ], 403);
        }

        // deactivated members (e.g. left the club) cannot log in
        if ($user && ! $user->active) {
            Auth::guard('api')->logout();
            AuthLogService::loginFailed($request->input('email'), 'Account inactive', $request);

            return response()->json([
                'status' => 'error',
                'message' => 'Dieses Konto ist deaktiviert. Bitte wende dich an deinen Trainer.',
                'code' => 'inactive',
            ], 403);
        }

        if ($user) {
            $user->failed_login_attempts = 0;
            $user->locked_until = null;
            $user->save();
            AuthLogService::loginSuccess($request);
        }

        $refreshTtlMinutes = config('jwt.refresh_ttl', 20160);
        $secureCookie = app()->environment('production');

        return response()->json([
            'status' => 'ok',
            'user' => Auth::guard('api')->user(),
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ])->withCookie(
            cookie('jwt_token', $token, $refreshTtlMinutes, '/', null, $secureCookie, true, false, 'Strict')
        );
    }

    private function lockedResponse()
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Zu viele fehlgeschlagene Anmeldeversuche. Das Konto ist für '.self::LOCKOUT_DURATION_MINUTES.' Minuten gesperrt.',
            'code' => 'locked',
        ], 403);
    }

    private function incrementFailedLoginAttempts(User $user): bool
    {
        $user->failed_login_attempts = $user->failed_login_attempts + 1;

        if ($user->failed_login_attempts >= self::MAX_FAILED_LOGIN_ATTEMPTS) {
            $user->locked_until = Carbon::now()->addMinutes(self::LOCKOUT_DURATION_MINUTES);
            $user->save();

            return true;
        }

        $user->save();

        return false;
    }
}
