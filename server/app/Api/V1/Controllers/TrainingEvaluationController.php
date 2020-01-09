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
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $filename = '/accounting_times/ul_abrechnung_'.strtolower($user->firstName).'_'.strtolower($user->familyName).'_'.$from->format("d_m_Y").'_'.$to->format("d_m_Y").'.xls';
        Excel::store(new TrainingTrainerExport($user, $from, $to), $filename, 'public', \Maatwebsite\Excel\Excel::XLS);
        return response()->json([
            'status' => 'ok',
            'fileName' => $filename
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

    /**
     * Get the account times for the given groups of all trainers.
     * @param Request $request
     */
    public function getAccountingTimeStatistics() {
        Log::info("getAccountingTimeStatistics");
        $groupIds = request()->query('groupIds');
        $year = request()->query('year');
        $cacheKey = 'accounting_time_statistics'.$year.$groupIds;
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        $series = [];

        $trainerIds = TrainingTrainer::with('training.groups', 'user')
            ->select('user_id')
            ->when($year, function ($query, $year) {
                $from = date($year . '-01-01');
                $to = date($year . '-12-31');
                $query->whereBetween('accounting_time_start', array($from, $to));
            })
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('training.groups', function ($query) use ($groupIds) {
                    $query->whereIn('groups.id', preg_split('/,/', $groupIds));
                });
            })
            ->whereHas('training', function ($q) {
                $q->where('evaluated', 1);
            })
            ->groupBy('user_id')
            ->pluck('user_id');


        foreach ($trainerIds as $trainerId) {
            //get all trainings for this trainer
            $trainerAccountingTimes = TrainingTrainer::with('training')
                ->when($year, function ($query, $year) {
                    $from = date($year . '-01-01');
                    $to = date($year . '-12-31');
                    $query->whereBetween('accounting_time_start', array($from, $to));
                })
                ->where('user_id', $trainerId)
                ->whereHas('training', function ($q) {
                    $q->where('evaluated', 1);
                })
                ->select(DB::raw('MONTH(accounting_time_start) as \'month\''), 'accounting_time_start', 'accounting_time_end')
                ->orderBy('month', 'asc')
                ->get();

            //sum up the accounting times per month
            $currentMonthAccountTime = 0;
            $lastMonth = 1;
            $monthArray = [];
            $size = sizeof($trainerAccountingTimes);
            $last = sizeof($trainerAccountingTimes) - 1;

            //sum the accounting hours per month
            for ($i = 0; $i < $size; $i++) {
                //the current month
                $currentMonth = $trainerAccountingTimes[$i]->month;
                //set the month from first and last row
                if ($i === 0) {
                    $lastMonth = $currentMonth;
                }

                //check if the month has changed since the last iteration
                //or if the entry is the last entry
                if ($currentMonth != $lastMonth || $i == $last) {

                    //if it's the last entry add the last account hours
                    if ($i == $last) {
                        $currentMonthAccountTime += $trainerAccountingTimes[$i]->getAccountingHoursAttribute();
                    }

                    $monthSum = new \stdClass();
                    $monthSum->month = $lastMonth;
                    $monthSum->accountingHours = round($currentMonthAccountTime,2);
                    array_push($monthArray, $monthSum);

                    $currentMonthAccountTime = 0;

                    $lastMonth = $currentMonth;
                }

                $currentMonthAccountTime += $trainerAccountingTimes[$i]->getAccountingHoursAttribute();

            }

            $result = new \stdClass();
            $result->trainer = User::where('id', $trainerId)->first();
            $result->data = $monthArray;
            array_push($series, $result);
        }

        Cache::put($cacheKey, $series , 15);

        return response()->json($series);
    }



}
