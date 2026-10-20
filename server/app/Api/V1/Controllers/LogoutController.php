<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuthLogService;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Log the user out (the session is destroyed)
     *
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        $userId = Auth::guard('api')->id();
        Auth::guard('api')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($userId) {
            AuthLogService::logout($userId, request());
        }

        return response()->json(['message' => 'Successfully logged out']);
    }
}
