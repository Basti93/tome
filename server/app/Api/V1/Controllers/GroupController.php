<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreGroupRequest;
use App\Group;
use App\Http\Controllers\Controller;
use App\Http\Resources\Group as GroupResource;
use Auth;

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
     * Get users.
     *
     * @return Response
     */
    public function index()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        return GroupResource::collection(Group::orderBy('branch_id')->orderBy('name')->paginate($per_page));
    }

    /**
     * Get users with pagination.
     *
     * @return Response
     */
    public function getByBranchId($id)
    {
        return GroupResource::collection(Group::where('branch_id', $id)->get());
    }

    public function store(StoreGroupRequest $request)
    {
        $group = new Group();

        $group->name = $request->input('name');
        $group->branch_id = $request->input('branchId');
        $group->save();

        $group->users()->sync($request->input('userIds'));

        return response()->json([
            'status' => 'ok'
        ], 201);

    }

    public function update(StoreGroupRequest $request, $id)
    {
        $group = Group::findOrFail($id);

        $group->name = $request->input('name');
        $group->branch_id = $request->input('branchId');
        $group->save();

        $group->users()->sync($request->input('userIds'));

        return response()->json([
            'status' => 'ok'
        ], 200);

    }

    public function destroy($id)
    {
        $group = Group::findOrFail($id);
        $group->delete();

        return response()->json([
            'status' => 'ok',
        ], 200);
    }

}
