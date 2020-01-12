<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreTrainingSeriesRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\TrainingSeries as TrainingSeriesResource;
use App\TrainingSeries;
use App\User;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class TrainingSeriesController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:read-training-series', ['only' => ['index', 'getById']]);
        $this->middleware('permission:create-training-series', ['only' => ['create', 'store']]);
        $this->middleware('permission:update-training-series', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete-training-series', ['only' => ['destroy']]);
    }

    /**
     * Get users with pagination.
     *
     * @return Response
     */
    public function index()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $groupIds = request()->query('groupIds');

        $trainings = TrainingSeries::latest()
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            })
            ->paginate($per_page);

        return TrainingSeriesResource::collection($trainings);
    }

    public function getById($id)
    {
        return TrainingSeriesResource::make(TrainingSeries::findOrFail($id));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return View::make('TrainingSeries');
    }

    public function store(StoreTrainingSeriesRequest $request)
    {
        $training = new TrainingSeries();

        $training->location_id = $request->input('locationId');

        $training->startTime = $request->input('startTime');
        $training->endTime = $request->input('endTime');
        $training->comment = $request->input('comment');
        $training->defer_until = $request->input('deferUntil') ? DateTime::createFromFormat(DateTime::ISO8601, $request->input('deferUntil')) : null;
        $training->weekdays = json_encode($request->input('weekdays'));
        $training->save();
        $training->trainers()->sync($request->input('trainerIds'));
        $training->groups()->sync($request->input('groupIds'));
        $training->contents()->sync($request->input('contentIds'));

        Artisan::call('training:series');

        return response()->json([
            'status' => 'ok'
        ], 201);

    }

    public function update(StoreTrainingSeriesRequest $request, $id)
    {
        $training = TrainingSeries::findOrFail($id);

        $training->location_id = $request->input('locationId');

        $training->startTime = $request->input('startTime');
        $training->endTime = $request->input('endTime');
        $training->comment = $request->input('comment');
        $training->defer_until = $request->input('deferUntil') ? DateTime::createFromFormat(DateTime::ISO8601, $request->input('deferUntil')) : null;
        $training->weekdays = json_encode($request->input('weekdays'));
        $training->update();
        $training->trainers()->sync($request->input('trainerIds'));
        $training->groups()->sync($request->input('groupIds'));
        $training->contents()->sync($request->input('contentIds'));

        Artisan::call('training:series');

        return response()->json([
            'status' => 'ok'
        ], 201);

    }

    public function destroy($id)
    {
        $series = TrainingSeries::findOrFail($id);
        if ($series) {
            $series->delete();
            return response()->json([
                'status' => 'ok',
            ], 201);
        } else {
            return response()->json(error);
        }
    }


}
