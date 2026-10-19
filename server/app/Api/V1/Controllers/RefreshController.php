<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuthLogService;
use Auth;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class RefreshController extends Controller
{
    /**
     * Refresh a token.
     *
     * The access token may already be expired (it lives 60 minutes) as long as it is inside the
     * refresh window (jwt.refresh_ttl), therefore this route has no jwt.auth middleware. The
     * token comes from the HttpOnly cookie (JwtFromCookie) or the Authorization header; the old
     * token is blacklisted by the JWT manager (rotation) and a fresh cookie is issued.
     *
     * @return JsonResponse
     */
    public function refresh()
    {
        $guard = Auth::guard('api');

        try {
            $token = $guard->refresh();
        } catch (JWTException) {
            // no token, malformed, blacklisted (e.g. after logout) or outside the refresh window
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401)->withCookie(cookie()->forget('jwt_token'));
        }

        $userId = $guard->setToken($token)->user()?->getAuthIdentifier();
        if ($userId) {
            AuthLogService::tokenRefresh((int) $userId, request());
        }

        $refreshTtlMinutes = config('jwt.refresh_ttl', 20160);
        $secureCookie = app()->environment('production');

        return response()->json([
            'status' => 'ok',
            'expires_in' => $guard->factory()->getTTL() * 60,
        ])->withCookie(
            cookie('jwt_token', $token, $refreshTtlMinutes, '/', null, $secureCookie, true, false, 'Strict')
        );
    }
}
