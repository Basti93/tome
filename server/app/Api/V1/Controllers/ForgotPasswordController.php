<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\ForgotPasswordRequest;
use App\Http\Controllers\Controller;
use App\Services\AuthLogService;
use App\User;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ForgotPasswordController extends Controller
{
    public function sendResetEmail(ForgotPasswordRequest $request)
    {
        $user = User::where('email', '=', $request->get('email'))->first();

        if (! $user) {
            throw new NotFoundHttpException;
        }

        $broker = $this->getPasswordBroker();
        $sendingResponse = $broker->sendResetLink($request->only('email'));

        if ($sendingResponse !== Password::RESET_LINK_SENT) {
            throw new HttpException(500);
        }

        AuthLogService::passwordResetRequested($request->get('email'), $request);

        return response()->json([
            'status' => 'ok',
        ], 200);
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return PasswordBroker
     */
    private function getPasswordBroker()
    {
        return Password::broker();
    }
}
