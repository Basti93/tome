<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreUserRequest;
use App\Http\Controllers\Controller;
use App\Branch;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTAuth;

class BranchController extends Controller
{
  /**
   * Create a new AuthController instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('permission:create-branch', ['only' => ['create', 'store']]);
    $this->middleware('permission:update-branch', ['only' => ['edit', 'update']]);
    $this->middleware('permission:delete-branch', ['only' => ['destroy']]);
  }

  /**
   * Get users with pagination.
   *
   * @return Response
   */
  public function index()
  {
    return response()->json(Branch::all());
  }

}
