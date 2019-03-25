<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\CreateUnregisteredUserRequest;
use App\Api\V1\Requests\StoreUserRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\NonApprovedUser as NonApprovedUserResource;
use App\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
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
    $this->middleware('permission:read-user', ['only' => ['index', 'getBySort', 'getTrainers']]);
    $this->middleware('permission:create-user', ['only' => ['create', 'store']]);
    $this->middleware('permission:update-user', ['only' => ['edit', 'update', 'approveUsersByIds']]);
    $this->middleware('permission:delete-user', ['only' => ['destroy']]);
  }

  /**
   * Get the authenticated User
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function me()
  {
    return response()->json(Auth::guard()->user());
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
    $unassigned = request()->query('unassigned');

    $users = User::latest()
      ->when($groupIds, function ($query, $groupIds) {
          $query->whereHas('groups', function ($query) use ($groupIds) {
              $query->whereIn('group_id', preg_split('/,/', $groupIds));
          });
      })
      ->when($branchId, function ($query, $branchId) {
        $query->whereHas('group', function ($query) use ($branchId) {
          $query->with(['branch' => function ($query) use ($branchId) {
            $query->where('id', $branchId);
          }]);
        });
      })
      ->when($unassigned, function ($query) {
        return $query->doesntHave('groups');
      });

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
    $unassigned = request()->query('unassigned');

    $users = User::orderBy($sortBy, $direction)
      //get users with group ids
      ->when($groupIds, function ($query, $groupIds) {
          $query->whereHas('groups', function ($query) use ($groupIds) {
              $query->whereIn('group_id', preg_split('/,/', $groupIds));
          });
      })
      //get users with branch id
      ->when($branchId, function ($query, $branchId) {
        $query->whereHas('group', function ($query) use ($branchId) {
          $query->with(['branch' => function ($query) use ($branchId) {
            $query->where('id', $branchId);
          }]);
        });
      })
      ->when($unassigned, function ($query) {
        return $query->doesntHave('groups');
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
    return response()->json(User::role('trainer')->get());
  }

  public function getNonapproved()
  {
    $users = User::whereApproved('0')->whereRegistered('1')->get();
    return NonApprovedUserResource::collection($users);
  }

  public function getNonRegistered()
  {
    $users = User::whereRegistered('0')->get();
    return NonApprovedUserResource::collection($users);
  }

  public function getNonApprovedCount()
  {
    $count = User::whereApproved('0')->whereRegistered('1')->count();
    return response()->json([
      'data' => $count
    ]);
  }

  public function update(StoreUserRequest $request, $id)
  {
    $user = User::findOrFail($id);

    //active boolean to 0 and 1
    if ($request->has('active')) {
      $user['active'] = $request->input('active') == 'true' ? 1 : 0;
    }
    if (!$user->update($request->all())) {
      throw new HttpException(500);
    }

    $user->groups()->sync($request->input('groupIds'));
    $user->trainerGroups()->sync($request->input('trainerGroupIds'));

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
    $user->save();

    $user->groups()->sync($request->input('groupIds'));

    return response()->json([
      'status' => 'ok'
    ], 201);

  }

  public function approveUser(Request $request, $id)
  {
    $userToApprove = User::findOrFail($id);
    if (!$userToApprove) {
      return response()->json([
        'status' => 'User not found'
      ], 422);
    }

    $userToApprove->assignRole(Role::findByName('member'));

    $groupId = $request->input('groupId');
    $userToApprove->update([
      'approved' => true,
      'group_id' => $groupId
    ]);


    $migrateUserId = $request->input('migrateUserId');
    if (!empty($migrateUserId)) {
      $migrateUser = User::findOrFail($migrateUserId);
      DB::table('training_participation')->where('user_id', $migrateUser->id)->update(['user_id' => $userToApprove->id]);
      $migrateUser->delete();
    }

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
