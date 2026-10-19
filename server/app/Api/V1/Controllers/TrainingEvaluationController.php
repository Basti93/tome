<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\ExportAccountingTimesRequest;
use App\Exports\TrainingTrainerExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\TrainingEvaluation as TrainingEvaluationResource;
use App\Training;
use App\TrainingTrainer;
use App\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $this->middleware('permission:update-training', ['only' => ['getAccountingTimes', 'getPastTrainingsForTrainer', 'trainingEvaluated', 'addParticipant', 'removeParticipant', 'updateAccountingTime', 'getAccountingTimeStatistics', 'exportAccountingTimes', 'completeTraining', 'getTrainingDetail']]);
    }

    public function getPastTrainingsForTrainer($userId)
    {
        $trainings = Training::where('start', '<=', DB::raw('NOW()'))
            ->where('deleted', false)
            ->whereHas('trainers', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('start', 'desc')
            ->limit(5)
            ->get();

        return TrainingEvaluationResource::collection($trainings);
    }

    /**
     * Streams the accounting times of one trainer as an XLSX download (nothing is stored on disk).
     * Trainers may only export their own times, admins may export anyone's.
     */
    public function exportAccountingTimes(ExportAccountingTimesRequest $request)
    {
        $from = DateTime::createFromFormat(DateTime::ISO8601, (string) $request->input('from'));
        $to = DateTime::createFromFormat(DateTime::ISO8601, (string) $request->input('to'));
        if ($from === false || $to === false) {
            throw ValidationException::withMessages(['from' => ['Ungültiger Zeitraum.']]);
        }

        $actingUser = $request->user('api');
        $userId = (int) $request->input('userId');
        if ($userId !== (int) $actingUser->id && ! $actingUser->hasRole('admin')) {
            abort(403, 'Forbidden');
        }
        $user = User::findOrFail($userId);

        $filename = 'abrechnung_'.$from->format('Y-m-d').'_'.$to->format('Y-m-d').'.xlsx';

        return Excel::download(new TrainingTrainerExport($user, $from, $to), $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * One training for the quick evaluation page (same access rule as completeTraining).
     */
    public function getTrainingDetail(Request $request, $trainingId)
    {
        $training = $this->findAccessibleTraining($request, $trainingId);

        return TrainingEvaluationResource::make($training);
    }

    /**
     * Quick evaluation in one step: attendance, trainer accounting times and the evaluated flag in one transaction.
     * Body: { attendeeIds: number[], trainerTimes: [{userId, start, end}] }
     * Calling it again with the same data changes nothing (idempotent).
     */
    public function completeTraining(Request $request, $trainingId)
    {
        $training = $this->findAccessibleTraining($request, $trainingId);

        $data = $request->validate([
            'attendeeIds' => 'present|array',
            'attendeeIds.*' => 'integer|distinct|exists:users,id',
            'trainerTimes' => 'sometimes|array',
            'trainerTimes.*.userId' => 'required|integer',
            'trainerTimes.*.start' => 'required|date',
            'trainerTimes.*.end' => 'required|date',
        ]);

        $trainerIds = TrainingTrainer::where('training_id', $training->id)->pluck('user_id')->all();
        $times = [];
        foreach ($data['trainerTimes'] ?? [] as $i => $row) {
            if (! in_array((int) $row['userId'], $trainerIds, true)) {
                throw ValidationException::withMessages(["trainerTimes.$i.userId" => ['Dieser Trainer gehört nicht zum Training.']]);
            }
            $start = Carbon::parse($row['start'])->timezone(config('app.timezone'));
            $end = Carbon::parse($row['end'])->timezone(config('app.timezone'));
            if ($end->lessThanOrEqualTo($start) || ! $start->isSameDay($end)) {
                throw ValidationException::withMessages(["trainerTimes.$i.end" => ['Die Endzeit muss nach der Startzeit am selben Tag liegen.']]);
            }
            $times[(int) $row['userId']] = [$start, $end];
        }

        DB::transaction(function () use ($training, $data, $times) {
            $attendeeIds = array_map('intval', $data['attendeeIds']);

            $existing = $training->trainingParticipation()->pluck('attend', 'user_id');
            foreach ($existing as $userId => $attend) {
                $shouldAttend = in_array((int) $userId, $attendeeIds, true) ? 1 : 0;
                if ((int) $attend !== $shouldAttend) {
                    $training->participants()->updateExistingPivot($userId, ['attend' => $shouldAttend], false);
                }
            }
            foreach ($attendeeIds as $userId) {
                if (! $existing->has($userId)) {
                    $training->participants()->attach($userId, ['attend' => 1]);
                }
            }

            foreach (TrainingTrainer::where('training_id', $training->id)->get() as $tt) {
                if (isset($times[$tt->user_id])) {
                    [$tt->accounting_time_start, $tt->accounting_time_end] = $times[$tt->user_id];
                } else {
                    // like the plain "evaluated" call: fall back to the training times
                    $tt->accounting_time_start = $tt->accounting_time_start ?: $training->start;
                    $tt->accounting_time_end = $tt->accounting_time_end ?: $training->end;
                }
                $tt->save();
            }

            $training->evaluated = true;
            $training->save();
        });

        return TrainingEvaluationResource::make(Training::findOrFail($training->id));
    }

    /** Admins may use any training, other users only trainings they are a trainer of. */
    private function findAccessibleTraining(Request $request, $trainingId): Training
    {
        $training = Training::where('deleted', false)->findOrFail($trainingId);
        $user = $request->user('api');
        if (! $user->hasRole('admin') && ! $training->trainers()->where('users.id', $user->id)->exists()) {
            abort(403, 'Forbidden');
        }

        return $training;
    }

    public function trainingEvaluated($trainingId)
    {
        $training = Training::findOrFail($trainingId);
        $trainingTrainers = TrainingTrainer::where('training_id', $trainingId)->get();
        foreach ($trainingTrainers as $tt) {
            // set the accounting time to the training time if no accounting time was set
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
            'status' => 'ok',
        ], 200);
    }

    public function updateAccountingTime(Request $request, $trainingId)
    {
        $trainerId = $request->input('trainerId');
        $start = DateTime::createFromFormat(DateTime::ISO8601, $request->input('start'));
        $end = DateTime::createFromFormat(DateTime::ISO8601, $request->input('end'));
        $this->updateAccountingTimeInter($trainerId, $trainingId, $start, $end);

        return response()->json([
            'status' => 'ok',
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

    public function removeParticipant($trainingId, $userId)
    {
        $training = Training::findOrFail($trainingId);
        if ($training->participants->contains($userId)) {
            $training->participants()->updateExistingPivot($userId, ['attend' => 0], false);

            return response()->json([
                'status' => 'ok',
            ], 200);
        }

        return response()->json([
            'status' => 'user not assigned to the training',
        ], 404);
    }

    public function addParticipant($trainingId, $userId)
    {
        $training = Training::findOrFail($trainingId);
        if ($training->participants->contains($userId)) {
            $training->participants()->updateExistingPivot($userId, ['attend' => 1], false);
        } else {
            $training->participants()->attach($userId, ['attend' => 1]);
        }

        return response()->json([
            'status' => 'ok',
        ], 200);
    }

    /**
     * Get the account times for the given groups of all trainers.
     *
     * @param  Request  $request
     */
    public function getAccountingTimeStatistics()
    {
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
                $from = date($year.'-01-01');
                $to = date($year.'-12-31');
                $query->whereBetween('accounting_time_start', [$from, $to]);
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
            // get all trainings for this trainer
            $trainerAccountingTimes = TrainingTrainer::with('training')
                ->when($year, function ($query, $year) {
                    $from = date($year.'-01-01');
                    $to = date($year.'-12-31');
                    $query->whereBetween('accounting_time_start', [$from, $to]);
                })
                ->where('user_id', $trainerId)
                ->whereHas('training', function ($q) {
                    $q->where('evaluated', 1);
                })
                ->select(DB::raw('MONTH(accounting_time_start) as \'month\''), 'accounting_time_start', 'accounting_time_end')
                ->orderBy('month', 'asc')
                ->get();

            if (! $trainerAccountingTimes->isEmpty()) {
                // sum up the accounting times per month
                $currentMonthAccountTime = 0;
                $monthArray = [];
                $currentMonth = $trainerAccountingTimes[0]->month;

                // sum the accounting hours per month
                for ($i = 0; $i < count($trainerAccountingTimes); $i++) {
                    if ($currentMonth != $trainerAccountingTimes[$i]->month) {
                        // new month detected -> store the old in the array
                        $monthSum = new \stdClass;
                        $monthSum->month = $currentMonth;
                        $monthSum->accountingHours = round($currentMonthAccountTime, 2);
                        array_push($monthArray, $monthSum);
                        $currentMonthAccountTime = 0;
                    }

                    // save the month of the last used entry
                    $currentMonth = $trainerAccountingTimes[$i]->month;
                    // add the account time of this entry to the month sum
                    $currentMonthAccountTime += $trainerAccountingTimes[$i]->getAccountingHoursAttribute();
                }
                // store last month because the loop will not store the last one (no new month detected in the end)
                $monthSum = new \stdClass;
                $monthSum->month = $currentMonth;
                $monthSum->accountingHours = round($currentMonthAccountTime, 2);
                array_push($monthArray, $monthSum);
            }

            $result = new \stdClass;
            $result->trainer = User::where('id', $trainerId)->first();
            $result->data = $monthArray;
            array_push($series, $result);
        }

        Cache::put($cacheKey, $series, 15);

        return response()->json($series);
    }
}
