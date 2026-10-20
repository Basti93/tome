<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\ResetPasswordRequest;
use App\Http\Controllers\Controller;
use App\Services\AuthLogService;
use App\User;
use Config;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Password;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

class ResetPasswordController extends Controller
{
    public function resetPassword(ResetPasswordRequest $request, JWTAuth $JWTAuth)
    {
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->reset($user, $password);
            }
        );

        if ($response !== Password::PASSWORD_RESET) {
            // expired, used or wrong token and unknown e-mail look the same (no account enumeration)
            return response()->json([
                'status' => 'error',
                'message' => 'Der Link ist ungültig oder abgelaufen. Bitte fordere über „Passwort vergessen“ einen neuen an.',
                'code' => 'invalid_token',
            ], 422);
        }

        $user = User::where('email', '=', $request->get('email'))->first();
        if ($user) {
            AuthLogService::passwordReset($user->id, $request);
        }

        if (! Config::get('boilerplate.reset_password.release_token')) {
            return response()->json([
                'status' => 'ok',
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'token' => $JWTAuth->fromUser($user),
        ]);
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @return array
     */
    protected function credentials(ResetPasswordRequest $request)
    {
        return $request->only(
            'email', 'password', 'password_confirmation', 'token'
        );
    }

    /**
     * Reset the given user's password.
     *
     * @param  User  $user
     * @param  string  $password
     * @return void
     */
    protected function reset($user, $password)
    {
        $user->password = $password;
        // setting a password via the reset/invite link proves control over the mailbox
        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
        }
        // an invited user now has e-mail and password; a new password also lifts a lockout
        $user->registered = true;
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();
    }
}
