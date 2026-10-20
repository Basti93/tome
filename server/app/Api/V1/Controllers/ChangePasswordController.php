<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\ChangePasswordRequest;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function changePassword(ChangePasswordRequest $request)
    {

        $user = User::findOrFail(Auth::user()->id);

        if (! Hash::check((string) $request->input('current_password'), (string) $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Das aktuelle Passwort ist falsch.',
                'errors' => ['current_password' => ['Das aktuelle Passwort ist falsch.']],
            ], 422);
        }

        $password = $request->input('password');

        $user->setPasswordAttribute($password);
        $user->save();

        return response()->json([
            'status' => 'ok',
        ], 201);
    }
}
