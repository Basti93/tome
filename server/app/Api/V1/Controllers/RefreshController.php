<?php

namespace App\Api\V1\Controllers;

use Symfony\Component\HttpKernel\Exception\HttpException;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\LoginRequest;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Auth;

class RefreshController extends Controller
{
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        $token = Auth::guard('api')->refresh();

        $refreshTtlMinutes = config('jwt.refresh_ttl', 20160);
        $secureCookie = app()->environment('production');

        return response()->json([
            'status' => 'ok',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
        ])->withCookie(
            cookie('jwt_token', $token, $refreshTtlMinutes, '/', null, $secureCookie, true, false, 'Strict')
        );
    }
}
