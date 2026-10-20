<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Training as TrainingResource;
use App\Training;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingPrepareController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:update-training', ['only' => ['getUpcomingTrainingsForTrainer', 'updateTrainingTime', 'updateLocation', 'updateComment', 'updateContent', 'groupNotes']]);
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

    /**
     * The notes trainers left after past trainings of the given groups (newest first, last 10).
     * Query: groupIds=1,2. Trainer endpoint only, notes are never part of a public response.
     */
    public function groupNotes(Request $request)
    {
        $groupIds = array_values(array_filter(array_map('intval', explode(',', (string) $request->query('groupIds'))), fn ($id) => $id > 0));
        if (! $groupIds) {
            return response()->json(['data' => []]);
        }

        $trainings = Training::where('start', '<', DB::raw('NOW()'))
            ->whereNotNull('note')
            ->where('note', '!=', '')
            ->whereHas('groups', fn ($q) => $q->whereIn('groups.id', $groupIds))
            ->orderBy('start', 'desc')
            ->limit(10)
            ->get();

        return response()->json(['data' => $trainings->map(fn (Training $t) => [
            'trainingId' => $t->id,
            'start' => DateTime::createFromFormat('Y-m-d H:i:s', $t->start)->format(DateTime::ATOM),
            'groupIds' => $t->group_ids,
            'note' => $t->note,
        ])->values()]);
    }

    public function updateTrainingTime(Request $request, $trainingId)
    {

        $training = Training::findOrFail($trainingId);

        $training->start = DateTime::createFromFormat(DateTime::ISO8601, $request->input('start'));
        $training->end = DateTime::createFromFormat(DateTime::ISO8601, $request->input('end'));
        $training->save();

        return response()->json([
            'status' => 'ok',
        ], 200);
    }

    public function updateLocation(Request $request, $trainingId)
    {

        $training = Training::findOrFail($trainingId);

        $training->location_id = $request->input('locationId');
        $training->save();

        return response()->json([
            'status' => 'ok',
        ], 200);
    }

    public function updateComment(Request $request, $trainingId)
    {

        $training = Training::findOrFail($trainingId);

        $training->comment = $request->input('comment');
        $training->save();

        return response()->json([
            'status' => 'ok',
        ], 200);
    }

    public function updateContent(Request $request, $trainingId)
    {

        $training = Training::findOrFail($trainingId);
        $training->contents()->sync($request->input('contentIds'));
        $training->save();

        return response()->json([
            'status' => 'ok',
        ], 200);
    }
}
