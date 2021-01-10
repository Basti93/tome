<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ZoomController;
use App\Location;
use App\Training;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Training as TrainingResource;

class TrainingPrepareController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:update-training', ['only' => ['getUpcomingTrainingsForTrainer', 'trainingPrepared', 'updateTrainingTime']]);
    }

    public function getUpcomingTrainingsForTrainer($userId)
    {
        $trainings = Training::where('start', '>=', DB::raw('NOW()'))
            ->whereHas('trainers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('start', 'asc')
            ->limit(5)
            ->get();

        return TrainingResource::collection($trainings);
    }

    public function trainingPrepared($trainingId)
    {
        $training = Training::findOrFail($trainingId);
        $training->prepared = 1;
        $training->save();
        return response()->json([
            'status' => 'ok'
        ], 200);
    }

    public function updateTrainingTime(Request $request, $trainingId)
    {

        $training = Training::findOrFail($trainingId);

        $training->start = DateTime::createFromFormat(DateTime::ISO8601, $request->input('start'));
        $training->end = DateTime::createFromFormat(DateTime::ISO8601, $request->input('end'));
        $training->save();

        //is online training
        if ($training->location_id === Location::where('name', 'Online')->first()->id) {
            $zoom = new ZoomController();
            $zoom->updateOrCreate($training);
        }

        return response()->json([
            'status' => 'ok'
        ], 200);
    }

    public function updateLocation(Request $request, $trainingId)
    {

        $training = Training::findOrFail($trainingId);

        $training->location_id = $request->input('locationId');
        $training->save();

        //is online training
        if ($training->location_id === Location::where('name', 'Online')->first()->id) {
            $zoom = new ZoomController();
            $zoom->updateOrCreate($training);
        }

        return response()->json([
            'status' => 'ok'
        ], 200);
    }

    public function updateComment(Request $request, $trainingId)
    {

        $training = Training::findOrFail($trainingId);

        $training->comment = $request->input('comment');
        $training->save();

        return response()->json([
            'status' => 'ok'
        ], 200);
    }

    public function updateContent(Request $request, $trainingId)
    {

        $training = Training::findOrFail($trainingId);
        $training->contents()->sync($request->input('contentIds'));
        $training->save();

        return response()->json([
            'status' => 'ok'
        ], 200);
    }


}
