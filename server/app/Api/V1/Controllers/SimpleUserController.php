<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreUserAbsenceRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\SimpleUser as SimpleUserResource;
use App\Services\PushNotifier;
use App\User;
use DateTime;

class SimpleUserController extends Controller
{
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

    public function storeAbsence(StoreUserAbsenceRequest $request, PushNotifier $notifier, $id)
    {
        $user = User::findOrFail($id);

        if ($user->absenceStart || $user->absenceEnd) {
            return response()->json([
                'status' => 'absence_exists',
                'message' => 'An absence is already stored for the user',
            ], 200);
        }

        $user->absenceStart = DateTime::createFromFormat(DateTime::ISO8601, $request->input('absenceStart'));
        $user->absenceEnd = DateTime::createFromFormat(DateTime::ISO8601, $request->input('absenceEnd'));
        $user->absenceReason = $request->input('absenceReason');

        $user->save();

        $fromFormatted = '';
        if ($user->absenceStart) {
            $fromFormatted = $user->absenceStart->format('d.m.y');
        }
        $untilFormatted = '';
        if ($user->absenceEnd) {
            $untilFormatted = $user->absenceEnd->format('d.m.y');
        }

        $notifier->absence($user, $fromFormatted, $untilFormatted, (string) $user->absenceReason);

        return response()->json([
            'status' => 'ok',
        ], 200);

    }

    /**
     * Get users who are trainers.
     *
     * @return Response
     */
    public function getTrainers()
    {
        return SimpleUserResource::collection(User::role('trainer')->get());
    }
}
