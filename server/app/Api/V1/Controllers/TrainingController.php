<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreTrainingRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ZoomController;
use App\Http\Resources\Training as TrainingResource;
use App\Location;
use App\Training;
use App\User;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainingController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:read-training', ['only' => ['getParticipationCount', 'getBySort', 'index', 'getById']]);
        $this->middleware('permission:create-training', ['only' => ['create', 'store']]);
        $this->middleware('permission:update-training', ['only' => ['edit', 'update', 'getUpcomingTrainingsForUser', 'trainingPrepared']]);
        $this->middleware('permission:checkin-training', ['only' => ['checkIn', 'checkOut']]);
        $this->middleware('permission:delete-training', ['only' => ['destroy']]);
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

        $trainings = Training::orderBy('start', 'desc')
            ->where('deleted', false)
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            })
            ->paginate($per_page);

        return TrainingResource::collection($trainings);
    }

    public function getById($id)
    {
        return TrainingResource::make(Training::findOrFail($id));
    }

    public function getBySort()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $direction = request()->query('direction');
        $sortBy = request()->query('sortBy');
        $groupIds = request()->query('groupIds');

        //mapping
        if ($sortBy === 'date') {
            $sortBy = 'start';
        } else if ($sortBy === 'locationId') {
            $sortBy = 'location_id';
        }

        $trainings = Training::orderBy($sortBy, $direction)
            ->where('deleted', false)
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            });


        $trainings = $trainings->paginate($per_page);
        return TrainingResource::collection($trainings);
    }

    public function getUpcomingTrainings()
    {
        $groupIds = request()->query('groupIds');
        $branchId = request()->query('branchId');

        $trainings = Training::where('start', '>=', DB::raw('NOW()'))
            ->where('deleted', false)
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
        if (new DateTime($training->start) >= new DateTime()) {
            return TrainingResource::make($training);
        }
        return response()->json([
            'status' => 'ok',
            'message' => 'Training with id '.$id.' could not be found or is in the past'
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
                $from = date($year . '-01-01');
                $to = date($year . '-12-31');
                $query->whereBetween('trainings.start', array($from, $to));
            })
            ->where('training_participation.attend', 1)
            ->where('users.active', 1)
            ->where('trainings.evaluated', 1)
            ->groupBy('users.id', 'users.firstName', 'users.familyName')
            ->select('users.id', 'users.firstName', 'users.familyName', DB::raw('count(*) as total'))
            ->orderBy('total', 'desc')
            ->get();
        return response()->json($result);
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

    public function deleteinfuture($trainingId)
    {
        $training = Training::findOrFail($trainingId);
        $training->deleted = 1;
        $training->save();
        return response()->json([
            'status' => 'ok'
        ], 200);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return View::make('Training');
    }

    public function store(StoreTrainingRequest $request)
    {
        $training = new Training();

        $training->location_id = $request->input('locationId');

        $training->start = DateTime::createFromFormat(DateTime::ISO8601, $request->input('start'));
        $training->end = DateTime::createFromFormat(DateTime::ISO8601, $request->input('end'));
        $training->comment = $request->input('comment');
        $training->automatic_attend = $request->input('automaticAttend') == 'true' ? 1 : 0;
        $training->save();
        $training->trainers()->sync($request->input('trainerIds'));
        $training->groups()->sync($request->input('groupIds'));


        $contents = $request->input('contentIds');
        if (!empty($contents)) {
            $training->contents()->sync($contents);
        }

        $participantIds = $request->input('participantIds');
        if (!empty($participantIds)) {
            $this->updateParticipants($participantIds, $training);
        }

        //is online training
        if ($training->location_id === Location::where('name', 'Online')->first()->id) {
            $zoom = new ZoomController();
            $zoom->create($training);
        }

        return response()->json([
            'status' => 'ok',
            'start' => $training->start->format('Y-m-d\TH:i:s.000\Z')
        ], 201);

    }

    public function checkIn($id, $userId)
    {
        $training = Training::findOrFail($id);
        $user = User::findOrFail($userId);
        return $this->checkInBasic($user, $training);
    }

    public function checkInUnregistered($id, $userId)
    {
        $user = User::whereActive('1')->findOrFail($userId);
        if ($user === null) {
            return response()->json([
                'status' => ' user not found'
            ], 404);

        }
        $training = Training::findOrFail($id);
        return $this->checkInBasic($user, $training);
    }

    private function checkInBasic($user, $training)
    {

        if (!$training->groups()->whereIn('groups.id', $user->getGroupIdsAttribute())->exists()) {
            return response()->json([
                'status' => 'User is not assigned to the training group'
            ], 403);
        }

        $this->updateParticipant($training, $user->id);

        return response()->json([
            'status' => 'ok'
        ], 201);
    }


    public function checkOutUnregistered(Request $request, $id, $userId)
    {
        $user = User::whereActive('1')->findOrFail($userId);
        if ($user === null) {
            return response()->json([
                'status' => 'user not found'
            ], 404);
        }

        $training = Training::findOrFail($id);
        return $this->checkOutBasic($user, $training, $request->input('reason'));
    }

    public function checkOut(Request $request, $id, $userId)
    {
        $training = Training::findOrFail($id);
        $user = User::findOrFail($userId);
        return $this->checkOutBasic($user, $training, $request->input('reason'));
    }

    private function checkOutBasic($user, $training, $reason)
    {

        if (!$training->groups()->whereIn('groups.id', $user->getGroupIdsAttribute())->exists()) {
            return response()->json([
                'status' => 'not_assigned',
                'message' => 'User is not assigned to the training group',
            ], 403);
        }

        //if training is in the next 24 hours
        if (strtotime($training->start) > time() && strtotime($training->start) < (time() + 86400)) {
            if (empty($reason)) {
                return response()->json([
                    'status' => 'cancel_needs_reason',
                    'message' => 'The training is in the next 24 hours and needs a reason for cancellation',
                ], 201);
            } else {
                if ($training->participants->contains($user->id)) {
                    $training->participants()->updateExistingPivot($user->id, array('attend' => 0, 'cancelreason' => $reason), false);
                } else {
                    $training->participants()->attach($user, ['attend' => 0, 'cancelreason' => $reason]);
                }
                Artisan::call('notification:cancelTrainingForTrainer', [
                    'userId' => $user->id,
                    'trainingId' => $training->id,
                    'cancelReason' => $reason,
                ]);
            }
        } else {
            if ($training->participants->contains($user->id)) {
                $training->participants()->updateExistingPivot($user->id, array('attend' => 0), false);
            } else {
                $training->participants()->attach($user, ['attend' => 0]);
            }

        }

        return response()->json([
            'status' => 'ok',
        ], 201);
    }

    public function update(StoreTrainingRequest $request, $id)
    {
        $training = Training::findOrFail($id);

        $training->location_id = $request->input('locationId');

        $training->start = DateTime::createFromFormat(DateTime::ISO8601, $request->input('start'));
        $training->end = DateTime::createFromFormat(DateTime::ISO8601, $request->input('end'));
        $training->comment = $request->input('comment');
        $training->automatic_attend = $request->input('automaticAttend') == 'true' ? 1 : 0;
        $training->update();
        $training->trainers()->sync($request->input('trainerIds'));
        $training->groups()->sync($request->input('groupIds'));

        $contents = $request->input('contentIds');
        if (!empty($contents)) {
            $training->contents()->sync($contents);
        }

        $participantIds = $request->input('participantIds');
        if (!empty($participantIds)) {
            $this->updateParticipants($participantIds, $training);
        }

        //is online training
        if ($training->location_id === Location::where('name', 'Online')->first()->id) {
            $zoom = new ZoomController();
            $zoom->updateOrCreate($training);
        }

        return response()->json([
            'status' => 'ok'
        ], 201);

    }

    /**
     * @param $participantIds
     * @param Training $training
     */
    private function updateParticipants($participantIds, Training $training): void
    {
        if (!empty($participantIds)) {
            foreach ($participantIds as $participantId) {
                $this->updateParticipant($training, $participantId);
            }
        }

        DB::table('training_participation')
            ->whereNotIn('user_id', $participantIds)
            ->where('training_id', $training->id)
            ->update(['attend' => 0]);
    }

    /**
     * @param Training $training
     * @param $participantId
     */
    private function updateParticipant(Training $training, $participantId): void
    {
        if ($training->participants->contains($participantId)) {
            $training->participants()->updateExistingPivot($participantId, array('attend' => 1), false);
        } else {
            $training->participants()->attach($participantId, ['attend' => 1]);
        }
    }

    public function destroy($id)
    {
        $training = Training::findOrFail($id);
        if ($training) {
            $training->delete();

            //is online training
            if ($training->location_id === Location::where('name', 'Online')->first()->id) {
                $zoom = new ZoomController();
                $zoom->delete($training);
            }

            return response()->json([
                'status' => 'ok',
            ], 201);
        } else {
            return response()->json(error);
        }
    }


}
