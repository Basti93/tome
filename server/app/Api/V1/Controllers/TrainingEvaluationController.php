<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\ExportAccountingTimesRequest;
use App\Api\V1\Requests\StoreTrainingRequest;
use App\Exports\TrainingTrainerExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\TrainingEvaluation as TrainingEvaluationResource;
use App\Training;
use App\TrainingTrainer;
use App\User;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class TrainingEvaluationController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:update-training', ['only' => ['getAccountingTimes', 'getPastTrainingsForTrainer', 'trainingEvaluated', 'addParticipant', 'removeParticipant']]);
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

    public function exportAccountingTimes(ExportAccountingTimesRequest $request)
    {
        $fromInput = $request->input('from');
        $toInput = $request->input('to');
        $from = DateTime::createFromFormat(DateTime::ISO8601, $fromInput);
        $to = DateTime::createFromFormat(DateTime::ISO8601, $toInput);
        $userId = $request->input('userId');
        $user = User::findOrFail($userId);

        $filename = 'ul_abrechnung_'.strtolower($user->firstName).'_'.strtolower($user->familyName).'_'.$from->format("d_m_Y").'_'.$to->format("d_m_Y").'.xlsx';
        Excel::store(new TrainingTrainerExport($user, $from, $to), $filename, 'public');
        return response()->json([
            'status' => 'ok',
            'url' => Storage::url("{$filename}")
        ]);
    }

    public function trainingEvaluated($trainingId)
    {
        $training = Training::findOrFail($trainingId);
        $trainingTrainers = TrainingTrainer::where('training_id', $trainingId)->get();
        foreach ($trainingTrainers as $tt) {
            //set the accounting time to the training time if no accounting time was set
            if (empty($tt->accounting_time_start)) {
                $tt->accounting_time_start = $training->start;
            }
            if (empty($tt->accounting_time_end)) {
                $tt->accounting_time_end = $training->end;
            }
            $tt->save();
        }

        $training->evaluated = 1;
        $training->save();
        return response()->json([
            'status' => 'ok'
        ], 200);
    }

    public function updateAccountingTime(Request $request, $trainingId)
    {
        $trainerId = $request->input('trainerId');
        $start = DateTime::createFromFormat(DateTime::ISO8601, $request->input('start'));
        $end = DateTime::createFromFormat(DateTime::ISO8601, $request->input('end'));
        $this->updateAccountingTimeInter($trainerId, $trainingId, $start, $end);

        return response()->json([
            'status' => 'ok'
        ], 200);
    }

    private function updateAccountingTimeInter($trainerId, $trainingId, $start, $end)
    {

        $trainingTrainer = TrainingTrainer::where('training_id', $trainingId)
            ->where('user_id', $trainerId)
            ->first();

        $trainingTrainer->accounting_time_start = $start;
        $trainingTrainer->accounting_time_end = $end;

        $trainingTrainer->save();
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
