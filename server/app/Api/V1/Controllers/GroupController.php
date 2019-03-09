<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreUserRequest;
use App\Http\Controllers\Controller;
use Auth;
use App\Group;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTAuth;

class GroupController extends Controller
{
  /**
   * Create a new AuthController instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('permission:create-group', ['only' => ['create', 'store']]);
    $this->middleware('permission:update-group', ['only' => ['edit', 'update']]);
    $this->middleware('permission:delete-group', ['only' => ['destroy']]);
  }

  /**
   * Get users with pagination.
   *
   * @return Response
   */
  public function index()
  {
    return response()->json(Group::all());
  }
  /**
   * Get users with pagination.
   *
   * @return Response
   */
  public function getByBranchId($id)
  {
    return response()->json(Group::where('branch_id', $id)->get());
  }

}
