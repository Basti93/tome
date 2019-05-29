<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\ChangePasswordRequest;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Config;
use Mail;

class ChangePasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', []);
    }

    public function changePassword(ChangePasswordRequest $request)
    {

        $user = User::findOrFail(Auth::user()->id);

        $password = $request->input('password');

        $user->setPasswordAttribute($password);
        $user->save();

        return response()->json([
            'status' => 'ok',
        ], 201);
    }

}
