<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\CreateUnregisteredUserRequest;
use App\Api\V1\Requests\StoreUserRequest;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use DateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        $this->middleware('permission:read-user', ['only' => ['index', 'getBySort', 'getTrainers', 'getBirthdayUsers']]);
        $this->middleware('permission:create-user', ['only' => ['create', 'store', 'createUnregistered']]);
        $this->middleware('permission:update-user', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-user', ['only' => ['destroy']]);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
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
        if (!empty($searchText)) {
            $users = User::latest()->when($searchText, function ($query, $searchText) {
                $query->where(function ($query) use ($searchText) {
                    $query->where('firstName', 'like', '%' . $searchText . '%')
                        ->orWhere('familyName', 'like', '%' . $searchText . '%');
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

        if (!empty(request('per_page'))) {
            $users = $users->paginate((int)request('per_page'));
        } else {
            $users = $users->get();
        }

        return response()->json($users);
    }

    /**
     * Get users by sorting
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getBySort()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');

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
                    $query->where('firstName', 'like', '%' . $searchText . '%')
                        ->orWhere('familyName', 'like', '%' . $searchText . '%');
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
            ->whereRaw("DATE_FORMAT( birthdate, '%m-%d') >= ?", array($start->format('m-d')))
            ->whereRaw("DATE_FORMAT( birthdate, '%m-%d') <= ?", array($end->format('m-d')))
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            })
            ->get();

        return response()->json($birthdayUser);
    }

    public function updateMe(StoreUserRequest $request)
    {

        $user = User::findOrFail(Auth::user()->id);

        //only allow update of groups for users with special rights
        if ($user->can('update-user')) {
            $user->groups()->sync($request->input('groupIds'));
            $user->trainerBranches()->sync($request->input('trainerBranchIds'));
        }

        $user->firstName = $request->input('firstName');
        $user->familyName = $request->input('familyName');
        $user->email = $request->input('email');
        $user->birthdate = DateTime::createFromFormat(DateTime::ISO8601, $request->input('birthdate'));

        //delete old image because there is a new one
        if (!empty($user->profile_image_name) && $user->profile_image_name !== $request->input('profileImageName')) {
            Log::info("delete profile image " . "/" . $user->profile_image_name);
            Storage::disk('public')->delete("/" . $user->profile_image_name);
        }
        //set new profile image
        $user->profile_image_name = $request->input('profileImageName');

        $user->save();

        return response()->json([
            'status' => 'ok'
        ], 201);

    }


    public function update(StoreUserRequest $request, $id)
    {
        $user = User::findOrFail($id);

        //active boolean to 0 and 1
        if ($request->has('active')) {
            $user->active = $request->input('active') == 'true' ? 1 : 0;
        }

        //delete old image because there is a new one
        if (!empty($user->profile_image_name) && $user->profile_image_name !== $request->input('profileImageName')) {
            Log::info("delete profile image " . "/" . $user->profile_image_name);
            Storage::disk('public')->delete("/" . $user->profile_image_name);
        }
        //set new profile image
        $user->profile_image_name = $request->input('profileImageName');


        if (!$user->update($request->all())) {
            throw new HttpException(500);
        }

        $user->groups()->sync($request->input('groupIds'));
        $user->trainerBranches()->sync($request->input('trainerBranchIds'));

        return response()->json([
            'status' => 'ok'
        ], 201);

    }

    public function createUnregistered(CreateUnregisteredUserRequest $request)
    {
        $user = new User();

        $user->registered = 0;
        $user->firstName = $request->input('firstName');
        $user->familyName = $request->input('familyName');
        $user->birthdate = $request->input('birthdate');
        //active boolean to 0 and 1
        if ($request->has('active')) {
            $user->active = $request->input('active') == 'true' ? 1 : 0;
        }
        //delete old image because there is a new one
        if (!empty($user->profile_image_name) && $user->profile_image_name !== $request->input('profileImageName')) {
            Log::info("delete profile image " . "/" . $user->profile_image_name);
            Storage::disk('public')->delete("/" . $user->profile_image_name);
        }
        //set new profile image
        $user->profile_image_name = $request->input('profileImageName');
        $user->save();

        $user->groups()->sync($request->input('groupIds'));

        return response()->json([
            'status' => 'ok'
        ], 201);

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

}
