<?php

namespace App\Api\V1\Controllers;

use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\LoginRequest;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Auth;
use App\User;
use App\Services\AuthLogService;

class LoginController extends Controller
{
    private const MAX_FAILED_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION_MINUTES = 15;
    /**
     * Log the user in
     *
     * @param LoginRequest $request
     * @param JWTAuth $JWTAuth
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request, JWTAuth $JWTAuth)
    {
        $credentials = $request->only(['email', 'password']);
        $user = User::where('email', $request->input('email'))->first();

        if ($user && $user->locked_until && Carbon::parse($user->locked_until)->isFuture()) {
            AuthLogService::loginFailed($request->input('email'), 'Account locked', $request);
            return response()->json([
                'status' => 'error',
                'message' => 'Account locked due to too many failed login attempts. Please try again later.',
            ], 403);
        }

        try {
            $token = Auth::guard('api')->attempt($credentials);

            if (!$token || !Auth::user()) {
                if ($user) {
                    $locked = $this->incrementFailedLoginAttempts($user);

                    if ($locked) {
                        AuthLogService::loginFailed($request->input('email'), 'Too many failed attempts', $request);
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Account locked due to too many failed login attempts. Please try again later.',
                        ], 403);
                    }
                }

                AuthLogService::loginFailed($request->input('email'), 'Invalid credentials', $request);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

        } catch (JWTException $e) {
            throw new HttpException(500);
        }

        // Check if email is verified
        if ($user && !$user->email_verified_at) {
            AuthLogService::loginFailed($request->input('email'), 'Email not verified', $request);
            return response()->json([
                'status' => 'error',
                'message' => 'Please verify your email before logging in.',
                'code' => 'email_not_verified'
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
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
        ])->withCookie(
            cookie('jwt_token', $token, $refreshTtlMinutes, '/', null, $secureCookie, true, false, 'Strict')
        );
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

