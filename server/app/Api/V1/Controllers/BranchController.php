<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreBranchRequest;
use App\Branch;
use App\Http\Controllers\Controller;

use App\Http\Resources\Branch as BranchResource;

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
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        return BranchResource::collection(Branch::orderBy('name')->paginate($per_page));
    }

    public function store(StoreBranchRequest $request)
    {
        $branch = new Branch();

        $branch->name = $request->input('name');
        $branch->short_name = $request->input('shortName');
        $branch->colorHex = $request->input('colorHex');

        $branch->save();

        return response()->json([
            'status' => 'ok'
        ], 201);

    }

    public function update(StoreBranchRequest $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $branch->name = $request->input('name');
        $branch->short_name = $request->input('shortName');
        $branch->colorHex = $request->input('colorHex');
        $branch->save();

        return response()->json([
            'status' => 'ok'
        ], 201);

    }


    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->delete();

        return response()->json([
            'status' => 'ok',
        ], 201);
    }


}
