<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\NotificationSubscribeRequest;
use App\Http\Controllers\Controller;
use App\NotificationToken;

class NotificationController extends Controller
{
    public function subscribe(NotificationSubscribeRequest $request)
    {
        $token = $request->input('firebaseToken');
        $userId = $request->input('userId');
        $foundEntry = NotificationToken::whereToken($token)->first();

        if ($foundEntry) {
            // reassign if the user changed; otherwise only refresh updated_at, which marks the
            // token as still in use (tome:retention deletes tokens not seen for 12 months)
            if ($foundEntry->user_id != $userId) {
                $foundEntry->user_id = $userId;
                $foundEntry->save();
            } else {
                $foundEntry->touch();
            }
        } else {
            // create new
            $newToken = new NotificationToken;
            $newToken->user_id = $userId;
            $newToken->token = $token;
            $newToken->save();
        }

        return response()->json([
            'status' => 'ok',
        ], 201);

    }
}
