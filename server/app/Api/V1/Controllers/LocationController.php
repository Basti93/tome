<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreLocationRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\Location as LocationResource;
use App\Location;

class LocationController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:create-location', ['only' => ['create', 'store']]);
        $this->middleware('permission:update-location', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-location', ['only' => ['destroy']]);
    }

    /**
     * Get locations.
     *
     * @return Response
     */
    public function index()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        return LocationResource::collection(Location::orderBy('name')->paginate($per_page));
    }

    public function store(StoreLocationRequest $request)
    {
        $location = new Location();

        $location->name = $request->input('name');
        $location->save();

        return response()->json([
            'status' => 'ok'
        ], 201);

    }

    public function update(StoreLocationRequest $request, $id)
    {
        $location = Location::findOrFail($id);

        $location->name = $request->input('name');
        $location->save();

        return response()->json([
            'status' => 'ok'
        ], 201);

    }


    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        $location->delete();

        return response()->json([
            'status' => 'ok',
        ], 201);
    }


}
