<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuthLogService;
use Auth;
use Illuminate\Http\JsonResponse;

class RefreshController extends Controller
{
    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh()
    {
        // Blacklist the old token to prevent reuse (refresh token rotation)
        $oldToken = Auth::guard('api')->token();

        $token = Auth::guard('api')->refresh();

        // Invalidate the old token
        Auth::guard('api')->manager()->blacklist()->add($oldToken);

        $userId = Auth::id();
        if ($userId) {
            AuthLogService::tokenRefresh($userId, request());
        }

        $refreshTtlMinutes = config('jwt.refresh_ttl', 20160);
        $secureCookie = app()->environment('production');

        return response()->json([
            'status' => 'ok',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ])->withCookie(
            cookie('jwt_token', $token, $refreshTtlMinutes, '/', null, $secureCookie, true, false, 'Strict')
        );
    }
}
