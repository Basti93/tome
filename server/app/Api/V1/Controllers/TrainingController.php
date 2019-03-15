<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Requests\StoreTrainingRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\Training as TrainingResource;
use App\Training;
use App\User;
use DateTime;
use Illuminate\Support\Facades\DB;

class TrainingController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:read-training', ['only' => ['getParticipationCount', 'getBySort', 'index']]);
        $this->middleware('permission:create-training', ['only' => ['create', 'store']]);
        $this->middleware('permission:update-training', ['only' => ['edit', 'update']]);
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
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            })
            ->paginate($per_page);

        return TrainingResource::collection($trainings);
    }

    public function getUpcomingTrainings()
    {
        $groupIds = request()->query('groupIds');

        $trainings = Training::where('start', '>=', DB::raw('NOW()'))
            ->orderBy('start', 'asc')
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            })
            ->limit(5)
            ->get();

        return TrainingResource::collection($trainings);
    }

    public function getBySort()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $direction = request()->query('direction');
        $sortBy = request()->query('sortBy');
        $groupIds = request()->query('groupIds');
        if ($sortBy === 'date') {
            $sortBy = 'start';
        }
        if ($sortBy === 'locationId') {
            $sortBy = 'location_id';
        }

        $trainings = Training::orderBy($sortBy, $direction)
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            })
            ->paginate($per_page);

        return TrainingResource::collection($trainings);
    }

    public function getBySortAndGroupId($groupId)
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $direction = request()->query('direction');
        $sortBy = request()->query('sortBy');
        if ($sortBy === 'date') {
            $sortBy = 'start';
        }

        $trainings = Training::orderBy($sortBy, $direction);

        $trainings = $trainings->whereHas('groups', function ($query) use ($groupId) {
            $query->where('group_id', $groupId);
        });

        $trainings = $trainings->paginate($per_page);
        return TrainingResource::collection($trainings);
    }

    public function getBySortAndBranchId($branchId)
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $direction = request()->query('direction');
        $sortBy = request()->query('sortBy');
        if ($sortBy === 'date') {
            $sortBy = 'start';
        }

        $trainings = Training::orderBy($sortBy, $direction)
            ->whereHas('groups', function ($query) use ($branchId) {
                $query->with(['branch' => function ($query) use ($branchId) {
                    $query->where('id', $branchId);
                }]);
            })
            ->paginate($per_page);

        return TrainingResource::collection($trainings);
    }

    public function getParticipationCount()
    {
        $groupIds = request()->query('groupIds');
        $year = request()->query('year');

        $result = DB::table('training_participation')
            ->join('users', 'training_participation.user_id', '=', 'users.id')
            ->join('trainings', 'training_participation.training_id', '=', 'trainings.id')
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereIn('users.group_id', preg_split('/,/', $groupIds));
            })
            ->when($year, function ($query, $year) {
                $from = date($year . '-01-01');
                $to = date($year . '-12-31');
                $query->whereBetween('trainings.start', array($from, $to));
            })
            ->where('training_participation.attend', 1)
            ->groupBy('users.id', 'users.firstName', 'users.familyName', 'users.group_id')
            ->select('users.id', 'users.firstName', 'users.familyName', 'users.group_id', DB::raw('count(*) as total'))
            ->orderBy('total', 'desc')
            ->get();
        return response()->json($result);
    }

    public function getTrainingTimeline($userId, $year)
    {
        $groupIds = request()->query('groupIds');

        $result = DB::table('training_trainer')
            ->join('users as trainers', 'training_trainer.user_id', '=', 'trainers.id')
            ->join('trainings', 'training_trainer.training_id', '=', 'trainings.id')
            ->when($groupIds, function ($query, $groupIds) {
                $query->join('training_participation', 'training_participation.training_id', '=', 'trainings.id')
                    ->join('users', 'training_participation.user_id', '=', 'users.id')
                    ->whereIn('users.group_id', preg_split('/,/', $groupIds));
            })
            ->when($year, function ($query, $year) {
                $from = date($year . '-01-01');
                $to = date($year . '-12-31');
                $query->whereBetween('trainings.start', array($from, $to));
            })
            ->where('training_trainer.user_id', $userId)
            ->groupBy(DB::raw('DATE_FORMAT(trainings.start, \'%m\')'))
            ->select(DB::raw('count(DISTINCT training_trainer.training_id) as total'), DB::raw('DATE_FORMAT(trainings.start, \'%m\') as \'month\''))
            ->orderBy('month', 'asc')
            ->get();
        return response()->json($result);
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
        $training->save();
        $training->trainers()->sync($request->input('trainerIds'));
        $training->participants()->sync($request->input('participantIds'));
        $training->groups()->sync($request->input('groupIds'));
        $training->contents()->sync($request->input('contentIds'));

        return response()->json([
            'status' => 'ok'
        ], 201);

    }

    public function checkIn($id, $userId)
    {
        $training = Training::findOrFail($id);
        $user = User::findOrFail($userId);
        $this->checkInBasic($user, $training);
    }

    private function checkInBasic($user, $training) {

        if ($training->groups()->where('groups.id', $user->group_id)->exists()) {
            $training->participants()->attach($user);
            return response()->json([
                'status' => 'ok'
            ], 201);
        } else {
            return response()->json([
                'status' => 'User is not assigned to the training group'
            ], 403);
        }
    }
    private function checkOutBasic($user, $training) {

        if ($training->groups()->where('groups.id', $user->group_id)->exists()) {
            $training->participants()->detach($user);
            return response()->json([
                'status' => 'ok'
            ], 201);
        } else {
            return response()->json([
                'status' => 'User is not assigned to the training group'
            ], 403);
        }
    }

    public function checkInUnregistered($id, $userId)
    {
        $user = User::whereRegistered('0')->whereActive('1')->findOrFail($userId);
        if ($user !== null) {
            $training = Training::findOrFail($id);
            $this->checkInBasic($user, $training);
        }
        return response()->json([
            'status' => 'unregistered user not found'
        ], 404);

    }

    public function checkOutUnregistered($id, $userId)
    {
        $user = User::whereRegistered('0')->whereActive('1')->findOrFail($userId);
        if ($user !== null) {
            $training = Training::findOrFail($id);

            $this->checkOutBasic($user, $training);
        }
        return response()->json([
            'status' => 'unregistered user not found'
        ], 404);
    }

    public function checkOut($id, $userId)
    {
        $training = Training::findOrFail($id);
        $user = User::findOrFail($userId);
        $this->checkOutBasic($user, $training);

    }

    public function update(StoreTrainingRequest $request, $id)
    {
        $training = Training::findOrFail($id);

        $training->location_id = $request->input('locationId');

        $training->start = DateTime::createFromFormat(DateTime::ISO8601, $request->input('start'));
        $training->end = DateTime::createFromFormat(DateTime::ISO8601, $request->input('end'));
        $training->comment = $request->input('comment');
        $training->update();
        $training->trainers()->sync($request->input('trainerIds'));
        $training->participants()->sync($request->input('participantIds'));
        $training->groups()->sync($request->input('groupIds'));
        $training->contents()->sync($request->input('contentIds'));

        return response()->json([
            'status' => 'ok'
        ], 201);

    }

    /**
     * Checks all trainings that are tomorrow and automatically assigns all the users who are not yet assigned to them.
     * Of course only the users which belong the correct training group.
     * Users which are already actively chosen to attend or not are not touched.
     */
    public function automaticAttendService() {
        $now = date('Y-m-d H:i:s');
        $tomorrow = date("Y-m-d H:i:s", time() + 86400);
        $closedTrainings = Training::whereBetween('start', array($now, $tomorrow))->get();

        foreach ($closedTrainings as $training) {
            $groups = $training->groups();
            $groupMembers = User::whereActive('1')->whereIn('group_id', $groups->pluck('group_id')->toArray())->get();

            foreach ($groupMembers as $groupMember) {
                //add all users who have not clicked on attending or not-attending
                if (!$training->participants()->where('user_id', $groupMember->id)->exists()) {
                    $training->participants()->attach($groupMember, ['attend' => 1]);
                }
            }
        }
    }


}
