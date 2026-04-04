<?php

namespace App\Services;

use App\AuthLog;
use Illuminate\Http\Request;

class AuthLogService
{
    public static function log(string $action, ?int $userId = null, string|array|null $details = null, ?Request $request = null)
    {
        AuthLog::create([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'details' => is_array($details) ? json_encode($details) : $details,
        ]);
    }

    public static function loginSuccess(?Request $request = null)
    {
        self::log('login', auth('api')->id(), null, $request);
    }

    public static function loginFailed(?string $email = null, ?string $reason = null, ?Request $request = null)
    {
        self::log('login_failed', null, ['email' => $email, 'reason' => $reason], $request);
    }

    public static function logout(?int $userId = null, ?Request $request = null)
    {
        self::log('logout', $userId, null, $request);
    }

    public static function tokenRefresh(?int $userId = null, ?Request $request = null)
    {
        self::log('token_refresh', $userId, null, $request);
    }

    public static function signUp(?string $email = null, ?Request $request = null)
    {
        self::log('signup', null, ['email' => $email], $request);
    }

    public static function emailVerified(?int $userId = null, ?Request $request = null)
    {
        self::log('email_verified', $userId, null, $request);
    }

    public static function passwordResetRequested(?string $email = null, ?Request $request = null)
    {
        self::log('password_reset_requested', null, ['email' => $email], $request);
    }

    public static function passwordReset(?int $userId = null, ?Request $request = null)
    {
        self::log('password_reset', $userId, null, $request);
    }
}
