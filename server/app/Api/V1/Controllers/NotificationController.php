<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\NotificationSubscribeRequest;
use App\Http\Controllers\Controller;
use App\NotificationToken;
use Auth;
use Illuminate\Http\Request;

class NotificationController extends Controller
{

    public function subscribe(NotificationSubscribeRequest $request)
    {
        $token = $request->input('firebaseToken');
        $userId = $request->input('userId');
        $foundEntry = NotificationToken::whereToken($token)->first();

        if ($foundEntry) {
            //update if user_id changed or do nothing
            if ($foundEntry->user_id != $userId) {
                $foundEntry->user_id = $userId;
                $foundEntry->save();
            }
        } else {
            //create new
            $newToken = new NotificationToken();
            $newToken->user_id = $userId;
            $newToken->token = $token;
            $newToken->save();
        }

        return response()->json([
            'status' => 'ok'
        ], 201);

    }

}
