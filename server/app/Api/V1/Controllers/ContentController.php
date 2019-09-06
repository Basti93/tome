<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreUserRequest;
use App\Http\Controllers\Controller;
use App\Content;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTAuth;

class ContentController extends Controller
{

  public function __construct()
  {
    $this->middleware('permission:create-content', ['only' => ['create', 'store']]);
    $this->middleware('permission:update-content', ['only' => ['edit', 'update']]);
    $this->middleware('permission:delete-content', ['only' => ['destroy']]);
  }


  public function index()
  {
    return response()->json(Content::orderByRaw("branch_id,  ISNULL(`order`) ASC, `order`")->get());
  }

}
