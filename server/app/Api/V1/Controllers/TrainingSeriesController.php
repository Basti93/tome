<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreTrainingSeriesRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\TrainingSeries as TrainingSeriesResource;
use App\Services\TrainingSeriesGenerator;
use App\TrainingSeries;
use DateTime;

class TrainingSeriesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:create-training-series', ['only' => ['store']]);
        $this->middleware('permission:update-training-series', ['only' => ['update']]);
        $this->middleware('permission:delete-training-series', ['only' => ['destroy']]);
    }

    public function index()
    {
        $per_page = empty(request('per_page')) ? 100 : (int) request('per_page');
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

    public function store(StoreTrainingSeriesRequest $request, TrainingSeriesGenerator $generator)
    {
        $this->save(new TrainingSeries, $request);
        $generator->generate();

        return response()->json(['status' => 'ok'], 201);
    }

    public function update(StoreTrainingSeriesRequest $request, TrainingSeriesGenerator $generator, $id)
    {
        $this->save(TrainingSeries::findOrFail($id), $request);
        $generator->generate();

        return response()->json(['status' => 'ok'], 201);
    }

    public function destroy($id)
    {
        TrainingSeries::findOrFail($id)->delete();

        return response()->json(['status' => 'ok'], 201);
    }

    private function save(TrainingSeries $series, StoreTrainingSeriesRequest $request): void
    {
        $series->location_id = $request->input('locationId');
        $series->startTime = $request->input('startTime');
        $series->endTime = $request->input('endTime');
        $series->comment = $request->input('comment');
        $series->defer_until = $request->input('deferUntil') ? DateTime::createFromFormat(DateTime::ISO8601, $request->input('deferUntil')) : null;
        $series->weekdays = array_values(array_map('intval', $request->input('weekdays')));
        $series->automatic_attend = $request->input('automaticAttend') == 'true' ? 1 : 0;
        $series->save();
        $series->trainers()->sync($request->input('trainerIds'));
        $series->groups()->sync($request->input('groupIds'));
        $series->contents()->sync($request->input('contentIds'));
    }
}
