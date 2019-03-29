<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\SimpleUser as SimpleUserResource;
use App\User;

class SimpleUserController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
    }


    public function index()
    {
        $groupIds = request()->query('groupIds');

        $users = User::where('active', true)
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            });

        return SimpleUserResource::collection($users->get());
    }


}
