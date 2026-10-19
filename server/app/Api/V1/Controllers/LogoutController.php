<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuthLogService;
use Auth;
use Illuminate\Http\JsonResponse;

class LogoutController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', []);
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return JsonResponse
     */
    public function logout()
    {
        $userId = Auth::id();
        Auth::guard('api')->logout();

        if ($userId) {
            AuthLogService::logout($userId, request());
        }

        return response()
            ->json(['message' => 'Successfully logged out'])
            ->withCookie(cookie()->forget('jwt_token'));
    }
}
