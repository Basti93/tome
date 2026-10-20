<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreTrainingRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\Training as TrainingResource;
use App\Services\TrainingAttendance;
use App\Training;
use App\User;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingController extends Controller
{
    public function __construct(private TrainingAttendance $attendance)
    {
        $this->middleware('permission:read-training', ['only' => ['getParticipationCount', 'getBySort', 'index', 'getById']]);
        $this->middleware('permission:create-training', ['only' => ['store']]);
        $this->middleware('permission:update-training', ['only' => ['update', 'trainingPrepared']]);
        $this->middleware('permission:checkin-training', ['only' => ['checkIn', 'checkOut']]);
        $this->middleware('permission:delete-training', ['only' => ['destroy', 'deleteinfuture']]);
    }

    public function index()
    {
        return TrainingResource::collection($this->paginated(Training::orderBy('start', 'desc')));
    }

    public function getById($id)
    {
        return TrainingResource::make(Training::findOrFail($id));
    }

    public function getBySort()
    {
        $sortBy = match (request()->query('sortBy')) {
            'date' => 'start',
            'locationId' => 'location_id',
            default => request()->query('sortBy'),
        };

        return TrainingResource::collection($this->paginated(Training::orderBy($sortBy, request()->query('direction'))));
    }

    public function getUpcomingTrainings()
    {
        $groupIds = request()->query('groupIds');
        $branchId = request()->query('branchId');

        $trainings = Training::where('start', '>=', DB::raw('NOW()'))
            ->orderBy('start', 'asc')
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            })
            ->when($branchId, function ($query, $branchId) {
                $query->whereHas('groups', function ($query) use ($branchId) {
                    $query->where('groups.branch_id', $branchId);
                });
            })
            ->limit(10)
            ->get();

        return TrainingResource::collection($trainings);
    }

    public function getUpcomingTraining($id)
    {
        $training = Training::findOrFail($id);
        if (new DateTime($training->start) >= new DateTime) {
            return TrainingResource::make($training);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Training with id '.$id.' could not be found or is in the past',
        ], 200);
    }

    public function getParticipationCount()
    {
        $groupIds = request()->query('groupIds');
        $year = request()->query('year');

        $result = DB::table('training_participation')
            ->join('users', 'training_participation.user_id', '=', 'users.id')
            ->join('trainings', 'training_participation.training_id', '=', 'trainings.id')
            ->when($groupIds, function ($query, $groupIds) {
                $query->join('user_group', 'user_group.user_id', '=', 'users.id')
                    ->whereIn('user_group.group_id', preg_split('/,/', $groupIds));
            })
            ->when($year, function ($query, $year) {
                $year = (int) $year;
                // the whole last day counts (a plain '-12-31' bound is midnight)
                $query->whereBetween('trainings.start', [$year.'-01-01 00:00:00', $year.'-12-31 23:59:59']);
            })
            ->where('training_participation.attend', 1)
            ->where('users.active', 1)
            ->where('trainings.evaluated', 1)
            ->groupBy('users.id', 'users.firstName', 'users.familyName')
            ->select('users.id', 'users.firstName', 'users.familyName', DB::raw('count(distinct trainings.id) as total'))
            ->orderBy('total', 'desc')
            ->get();

        return response()->json($result);
    }

    public function trainingPrepared($trainingId)
    {
        $training = Training::findOrFail($trainingId);
        $training->prepared = 1;
        $training->save();

        return response()->json(['status' => 'ok'], 200);
    }

    public function deleteinfuture($trainingId)
    {
        Training::findOrFail($trainingId)->delete();

        return response()->json(['status' => 'ok'], 200);
    }

    public function store(StoreTrainingRequest $request)
    {
        $training = $this->save(new Training, $request);

        return response()->json([
            'status' => 'ok',
            'start' => $training->start->format('Y-m-d\TH:i:s.000\Z'),
        ], 201);
    }

    public function update(StoreTrainingRequest $request, $id)
    {
        $this->save(Training::findOrFail($id), $request);

        return response()->json(['status' => 'ok'], 201);
    }

    public function checkIn($id, $userId)
    {
        return $this->checkInBasic(User::findOrFail($userId), Training::findOrFail($id));
    }

    public function checkInUnregistered($id, $userId)
    {
        $user = User::whereActive('1')->findOrFail($userId);

        return $this->checkInBasic($user, Training::findOrFail($id));
    }

    public function checkOut(Request $request, $id, $userId)
    {
        return $this->checkOutBasic(User::findOrFail($userId), Training::findOrFail($id), $request->input('reason'));
    }

    public function checkOutUnregistered(Request $request, $id, $userId)
    {
        $user = User::whereActive('1')->findOrFail($userId);

        return $this->checkOutBasic($user, Training::findOrFail($id), $request->input('reason'));
    }

    /** Flagged-as-deleted trainings can still be removed for good. */
    public function destroy($id)
    {
        Training::withTrashed()->findOrFail($id)->forceDelete();

        return response()->json(['status' => 'ok'], 201);
    }

    private function checkInBasic(User $user, Training $training)
    {
        if (! $this->attendance->isAssigned($training, $user)) {
            return response()->json(['status' => 'User is not assigned to the training group'], 403);
        }

        $this->attendance->checkIn($training, $user->id);

        return response()->json(['status' => 'ok'], 201);
    }

    private function checkOutBasic(User $user, Training $training, ?string $reason)
    {
        if (! $this->attendance->isAssigned($training, $user)) {
            return response()->json([
                'status' => 'not_assigned',
                'message' => 'User is not assigned to the training group',
            ], 403);
        }

        if (! $this->attendance->checkOut($training, $user, $reason)) {
            return response()->json([
                'status' => 'cancel_needs_reason',
                'message' => 'The training is in the next 24 hours and needs a reason for cancellation',
            ], 201);
        }

        return response()->json(['status' => 'ok'], 201);
    }

    /** Paginate a training query, optionally filtered by the `groupIds` query parameter. */
    private function paginated($query)
    {
        $perPage = empty(request('per_page')) ? 10 : (int) request('per_page');

        return $query
            ->when(request()->query('groupIds'), function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            })
            ->paginate($perPage);
    }

    private function save(Training $training, StoreTrainingRequest $request): Training
    {
        $training->location_id = $request->input('locationId');
        $training->start = DateTime::createFromFormat(DateTime::ISO8601, $request->input('start'));
        $training->end = DateTime::createFromFormat(DateTime::ISO8601, $request->input('end'));
        $training->comment = $request->input('comment');
        $training->automatic_attend = $request->input('automaticAttend') == 'true' ? 1 : 0;
        $training->save();
        $training->trainers()->sync($request->input('trainerIds'));
        $training->groups()->sync($request->input('groupIds'));

        $contents = $request->input('contentIds');
        if (! empty($contents)) {
            $training->contents()->sync($contents);
        }

        $participantIds = $request->input('participantIds');
        if (! empty($participantIds)) {
            $this->attendance->setAttendees($training, $participantIds);
        }

        return $training;
    }
}
