<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\CreateUnregisteredUserRequest;
use App\Api\V1\Requests\StoreUserRequest;
use App\Http\Controllers\Controller;
use App\Services\AuthLogService;
use App\Services\NotificationPreferenceService;
use App\Support\ProfileImage;
use App\User;
use Auth;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', []);
        $this->middleware('permission:read-user', ['only' => ['index', 'getBySort', 'getTrainers', 'getBirthdayUsers', 'getAllAbsenceUsers']]);
        $this->middleware('permission:create-user', ['only' => ['create', 'store', 'createUnregistered']]);
        $this->middleware('permission:update-user', ['only' => ['edit', 'update', 'removeAbsence', 'invite']]);
        $this->middleware('permission:delete-user', ['only' => ['destroy']]);
    }

    /**
     * Get the authenticated User
     *
     * @return JsonResponse
     */
    public function me()
    {
        return response()->json(Auth::guard('api')->user());
    }

    /**
     * Get users with pagination.
     *
     * @return Response
     */
    public function index()
    {
        $groupIds = request()->query('groupIds');
        $branchId = request()->query('branchId');
        $searchText = request()->query('searchText');

        $users = null;
        if (! empty($searchText)) {
            $users = User::latest()->when($searchText, function ($query, $searchText) {
                $query->where(function ($query) use ($searchText) {
                    $query->where('firstName', 'like', '%'.$searchText.'%')
                        ->orWhere('familyName', 'like', '%'.$searchText.'%');
                });
            });
        } else {
            $users = User::latest()->when($groupIds, function ($query, $groupIds) {
                $query->where(function ($query) use ($groupIds) {
                    $query->whereHas('groups', function ($query) use ($groupIds) {
                        $query->whereIn('group_id', preg_split('/,/', $groupIds));
                    });
                });
            })
                ->when($branchId, function ($query, $branchId) {
                    $query->whereHas('groups', function ($query) use ($branchId) {
                        $query->where('groups.branch_id', $branchId);
                    });
                });
        }

        if (! empty(request('per_page'))) {
            $users = $users->paginate((int) request('per_page'));
        } else {
            $users = $users->get();
        }

        return response()->json($users);
    }

    /**
     * Get users by sorting
     *
     * @return AnonymousResourceCollection
     */
    public function getBySort()
    {
        $per_page = empty(request('per_page')) ? 10 : (int) request('per_page');

        $direction = request()->query('direction');
        $sortBy = request()->query('sortBy');
        $groupIds = request()->query('groupIds');
        $branchId = request()->query('branchId');
        $searchText = request()->query('searchText');

        $users = User::orderBy($sortBy, $direction)
            ->when($groupIds, function ($query, $groupIds) {
                $query->where(function ($query) use ($groupIds) {
                    $query->whereHas('groups', function ($query) use ($groupIds) {
                        $query->whereIn('group_id', preg_split('/,/', $groupIds));
                    })
                        ->orWhereDoesntHave('groups');
                });
            })
            ->when($branchId, function ($query, $branchId) {
                $query->whereHas('groups', function ($query) use ($branchId) {
                    $query->where('groups.branch_id', $branchId);
                });
            })
            ->when($searchText, function ($query, $searchText) {
                $query->where(function ($query) use ($searchText) {
                    $query->where('firstName', 'like', '%'.$searchText.'%')
                        ->orWhere('familyName', 'like', '%'.$searchText.'%');
                });
            })
            ->paginate($per_page);

        return response()->json($users);
    }

    /**
     * Get users who are trainers.
     *
     * @return Response
     */
    public function getTrainers()
    {
        $branchIds = request()->query('branchIds');

        $trainers = User::role('trainer')
            ->when($branchIds, function ($query, $branchIds) {
                $query->whereHas('trainerBranches', function ($query) use ($branchIds) {
                    $query->whereIn('branch_id', preg_split('/,/', $branchIds));
                });
            })->get();

        return response()->json($trainers);
    }

    public function getBirthdayUsers()
    {
        $groupIds = request()->query('groupIds');
        $start = new DateTime(request()->query('start'));
        $end = new DateTime(request()->query('end'));

        $birthdayUser = User::select('firstName', 'familyName', 'birthdate')
            ->where('active', 1)
            ->whereRaw("DATE_FORMAT( birthdate, '%m-%d') >= ?", [$start->format('m-d')])
            ->whereRaw("DATE_FORMAT( birthdate, '%m-%d') <= ?", [$end->format('m-d')])
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            })
            ->get()
            ->map(fn (User $user) => [
                'firstName' => $user->firstName,
                'familyName' => $user->familyName,
                'birthdate' => $user->birthdate,
            ]);

        return response()->json($birthdayUser);
    }

    public function updateMe(StoreUserRequest $request)
    {

        $user = User::findOrFail(Auth::user()->id);

        // only allow update of groups for users with special rights
        if ($user->can('update-user')) {
            $user->groups()->sync($request->input('groupIds'));
            $user->trainerBranches()->sync($request->input('trainerBranchIds'));
        }

        $user->firstName = $request->input('firstName');
        $user->familyName = $request->input('familyName');
        $user->email = $request->input('email');

        // Handle birthdate - only set if provided and not empty
        $birthdateInput = $request->input('birthdate');
        if (! empty($birthdateInput)) {
            $parsedDate = DateTime::createFromFormat(DateTime::ISO8601, $birthdateInput);
            if ($parsedDate !== false) {
                $user->birthdate = $parsedDate;
            }
        } else {
            // Explicitly set to null if empty to avoid storing invalid dates
            $user->birthdate = null;
        }

        // delete old image because there is a new one (only files that match the profile image naming scheme)
        if (! empty($user->profile_image_name) && $user->profile_image_name !== $request->input('profileImageName')) {
            ProfileImage::deleteIfValid($user->profile_image_name);
        }
        // set new profile image
        $user->profile_image_name = $request->input('profileImageName');

        $user->save();

        return response()->json([
            'status' => 'ok',
        ], 201);

    }

    public function update(StoreUserRequest $request, $id)
    {
        $user = User::findOrFail($id);

        // active boolean to 0 and 1
        if ($request->has('active')) {
            $user->active = $request->input('active') == 'true' ? 1 : 0;
        }

        // delete old image because there is a new one (only files that match the profile image naming scheme)
        if (! empty($user->profile_image_name) && $user->profile_image_name !== $request->input('profileImageName')) {
            ProfileImage::deleteIfValid($user->profile_image_name);
        }
        // set new profile image
        $user->profile_image_name = $request->input('profileImageName');

        if (! $user->update($request->all())) {
            throw new HttpException(500);
        }

        $user->groups()->sync($request->input('groupIds'));
        $user->trainerBranches()->sync($request->input('trainerBranchIds'));

        return response()->json([
            'status' => 'ok',
        ], 201);

    }

    public function createUnregistered(CreateUnregisteredUserRequest $request)
    {
        $user = new User;

        $user->registered = 0;
        $user->firstName = $request->input('firstName');
        $user->familyName = $request->input('familyName');
        $user->birthdate = $request->input('birthdate');
        // active boolean to 0 and 1
        if ($request->has('active')) {
            $user->active = $request->input('active') == 'true' ? 1 : 0;
        }
        // delete old image because there is a new one (only files that match the profile image naming scheme)
        if (! empty($user->profile_image_name) && $user->profile_image_name !== $request->input('profileImageName')) {
            ProfileImage::deleteIfValid($user->profile_image_name);
        }
        // set new profile image
        $user->profile_image_name = $request->input('profileImageName');
        $user->save();

        $user->groups()->sync($request->input('groupIds'));

        return response()->json([
            'status' => 'ok',
        ], 201);

    }

    public function getAllAbsenceUsers()
    {
        $groupIds = request()->query('groupIds');
        $per_page = empty(request('per_page')) ? 10 : (int) request('per_page');

        $now = date('Y-m-d H:i:s');
        $users = User::whereNotNull('absenceStart')
            ->where('active', true)
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            })->orderBy('absenceEnd')->paginate($per_page);

        return response()->json($users);
    }

    public function removeAbsence($id)
    {
        $user = User::findOrFail($id);

        $user->absenceStart = null;
        $user->absenceEnd = null;
        $user->absenceReason = null;

        $user->save();

        return response()->json([
            'status' => 'ok',
        ], 200);

    }

    /**
     * Invite a user: sends a "set your password" mail that reuses the password reset token and link.
     */
    public function invite($id)
    {
        $user = User::findOrFail($id);

        if (empty($user->email)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The user has no email address',
            ], 422);
        }

        $user->sendInvitation(Password::createToken($user));
        AuthLogService::log('invite_sent', $user->id, ['by' => Auth::guard('api')->id()], request());

        return response()->json([
            'status' => 'ok',
        ], 200);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user) {
            $user->delete();

            return response()->json([
                'status' => 'ok',
            ], 201);
        } else {
            return response()->json(error);
        }
    }

    /**
     * Get notification preferences for the authenticated user
     *
     * @return JsonResponse
     */
    public function getNotificationPreferences()
    {
        $user = Auth::guard('api')->user();
        $service = new NotificationPreferenceService;

        return response()->json([
            'notification_preferences' => $service->getPreferences($user),
        ], 200);
    }

    /**
     * Update notification preferences for the authenticated user
     *
     * @return JsonResponse
     */
    public function updateNotificationPreferences(Request $request)
    {
        $user = Auth::guard('api')->user();
        $service = new NotificationPreferenceService;

        $preferences = $request->input('notification_preferences', []);

        $service->updatePreferences($user, $preferences);

        return response()->json([
            'status' => 'ok',
            'notification_preferences' => $user->notification_preferences,
        ], 200);
    }
}
