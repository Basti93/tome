<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrainingEvaluation as TrainingEvaluationResource;
use App\Training;
use App\TrainingTrainer;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingEvaluationController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:update-training', ['only' => ['getPastTrainingsForTrainer', 'trainingEvaluated', 'addParticipant', 'removeParticipant']]);
    }

    public function getPastTrainingsForTrainer($userId)
    {
        $trainings = Training::where('start', '<=', DB::raw('NOW()'))
            ->whereHas('trainers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('start', 'desc')
            ->limit(5)
            ->get();

        return TrainingEvaluationResource::collection($trainings);
    }

    public function trainingEvaluated($trainingId)
    {
        $training = Training::findOrFail($trainingId);
        $training->evaluated = 1;
        $training->save();
        return response()->json([
            'status' => 'ok'
        ], 200);
    }

    public function updateAccountingTime(Request $request, $trainingId)
    {

        $trainerId = $request->input('trainerId');
        $trainingTrainer = TrainingTrainer::where('training_id', $trainingId)
            ->where('user_id', $trainerId)
            ->first();

        $trainingTrainer->accounting_time_start = DateTime::createFromFormat(DateTime::ISO8601, $request->input('start'));
        $trainingTrainer->accounting_time_end = DateTime::createFromFormat(DateTime::ISO8601, $request->input('end'));
        $trainingTrainer->save();

        return response()->json([
            'status' => 'ok'
        ], 200);
    }

    public function removeParticipant($trainingId, $userId) {
        $training = Training::findOrFail($trainingId);
        if ($training->participants->contains($userId)) {
            $training->participants()->updateExistingPivot($userId, array('attend' => 0), false);
            return response()->json([
                'status' => 'ok'
            ], 200);
        }
        return response()->json([
            'status' => 'user not assigned to the training'
        ], 404);
    }

    public function addParticipant($trainingId, $userId) {
        $training = Training::findOrFail($trainingId);
        if ($training->participants->contains($userId)) {
            $training->participants()->updateExistingPivot($userId, array('attend' => 1), false);
            return response()->json([
                'status' => 'ok'
            ], 200);
        }
        return response()->json([
            'status' => 'user not assigned to the training'
        ], 404);
    }



}
