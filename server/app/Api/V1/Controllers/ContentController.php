<?php

namespace App\Api\V1\Controllers;

use App\Content;
use App\Http\Controllers\Controller;

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
        return response()->json(Content::orderByRaw('branch_id,  ISNULL(`order`) ASC, `order`')->get());
    }
}
