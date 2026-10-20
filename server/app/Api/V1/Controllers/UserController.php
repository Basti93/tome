<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\CreateUnregisteredUserRequest;
use App\Api\V1\Requests\StoreUserRequest;
use App\Http\Controllers\Controller;
use App\Services\AuthLogService;
use App\Services\NotificationPreferenceService;
use App\Support\PasswordBrokers;
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
        $this->middleware('auth:sanctum');
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

        // only allow update of groups for users with special rights; the trainer branches (what a trainer may
        // manage) only an admin can change, also for their own account. A missing key leaves the relation alone.
        if ($user->can('update-user')) {
            if ($request->has('groupIds')) {
                $user->groups()->sync($request->input('groupIds'));
            }
            if ($request->has('trainerBranchIds') && $user->hasRole('admin')) {
                $user->trainerBranches()->sync($request->input('trainerBranchIds'));
            }
        }

        $user->firstName = $request->input('firstName');
        $user->familyName = $request->input('familyName');
        // A changed address stays verified: the user is logged in, and an unverified address would lock them out
        // at the next login after a typo (an admin can fix it and re-invite). Changing it needs the session only.
        if ($request->has('email')) {
            $user->email = $request->input('email');
        }

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
        $actor = Auth::guard('api')->user();
        $actorIsAdmin = $actor->hasRole('admin');

        // Authorization first (nothing is changed or deleted before all checks passed).
        // Admins and trainers can only be edited by an admin; so can everything that decides who may log in or
        // act as a trainer: `active` and trainerBranchIds of accounts with a role or a password.
        $forbidden = fn (string $message) => response()->json(['status' => 'error', 'message' => $message], 403);
        $privileged = $user->hasAnyRole(['admin', 'trainer']) || ! empty($user->password);
        if (! $actorIsAdmin) {
            if ($user->hasRole('admin')) {
                return $forbidden('Admin-Konten kann nur ein Admin ändern.');
            }
            if ($privileged) {
                if ($request->has('active') && ($request->input('active') == 'true' ? 1 : 0) !== (int) $user->active) {
                    return $forbidden('Ob ein Konto mit Anmeldung aktiv ist, kann nur ein Admin ändern.');
                }
                if ($request->has('trainerBranchIds')) {
                    $current = $user->trainerBranches()->pluck('branches.id')->map(fn ($v) => (int) $v)->sort()->values()->all();
                    $requested = collect($request->input('trainerBranchIds', []))->map(fn ($v) => (int) $v)->unique()->sort()->values()->all();
                    if ($current !== $requested) {
                        return $forbidden('Die Trainer-Sparten eines Kontos mit Anmeldung kann nur ein Admin ändern.');
                    }
                }
            }
            // Changing the address of an account that can log in would allow taking it over via "Passwort vergessen"
            if ($request->has('email') && $request->input('email') !== $user->email && ! empty($user->password)) {
                return $forbidden('Die E-Mail-Adresse eines Kontos mit Anmeldung kann nur ein Admin ändern.');
            }
        }

        // active boolean to 0 and 1
        if ($request->has('active')) {
            $user->active = $request->input('active') == 'true' ? 1 : 0;
        }

        if ($request->has('profileImageName')) {
            // delete old image because there is a new one (only files that match the profile image naming scheme)
            if (! empty($user->profile_image_name) && $user->profile_image_name !== $request->input('profileImageName')) {
                ProfileImage::deleteIfValid($user->profile_image_name);
            }
            $user->profile_image_name = $request->input('profileImageName');
        }

        // only these fields are editable (never password, notification preferences, ...)
        $fields = $request->only(['firstName', 'familyName', 'birthdate']);
        if ($request->has('email')) {
            $fields['email'] = $request->input('email');
        }
        if (! $user->update($fields)) {
            throw new HttpException(500);
        }

        // a missing key leaves the relation alone (sync(null) would detach everything)
        if ($request->has('groupIds')) {
            $user->groups()->sync($request->input('groupIds'));
        }
        if ($request->has('trainerBranchIds')) {
            $user->trainerBranches()->sync($request->input('trainerBranchIds'));
        }

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
        $user->email = $request->input('email');
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
                'message' => 'Der Benutzer hat keine E-Mail-Adresse.',
            ], 422);
        }

        $user->sendInvitation(PasswordBrokers::get('invites')->createToken($user));
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

        // only the known types are stored (unknown keys are dropped), each as a real boolean
        $known = array_keys($service->getDefaultPreferences());
        $rules = ['notification_preferences' => ['nullable', 'array']];
        foreach ($known as $type) {
            $rules["notification_preferences.$type"] = ['boolean'];
        }
        $validated = $request->validate($rules);
        $preferences = [];
        foreach ($known as $type) {
            if (isset($validated['notification_preferences']) && array_key_exists($type, $validated['notification_preferences'])) {
                $preferences[$type] = (bool) $validated['notification_preferences'][$type];
            }
        }

        $service->updatePreferences($user, $preferences);

        return response()->json([
            'status' => 'ok',
            'notification_preferences' => $user->notification_preferences,
        ], 200);
    }
}
