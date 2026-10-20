<?php

namespace App\Api\V1\Controllers;

use App\Group;
use App\Http\Controllers\Controller;
use App\Training;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Attendance overview of one group: who attended which evaluated training in a period.
 * Trainers of the group's branch and admins only.
 */
class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'groupId' => 'required|integer',
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
        ]);

        $group = Group::findOrFail($data['groupId']);
        /** @var User $actor */
        $actor = $request->user('api');
        $mayView = $actor->hasRole('admin')
            || ($actor->hasRole('trainer') && $actor->trainerBranches()->where('branches.id', $group->branch_id)->exists());
        abort_unless($mayView, 403, 'Du darfst die Anwesenheit dieser Gruppe nicht einsehen.');

        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->endOfDay();

        // evaluated, not deleted (SoftDeletes) trainings of the group in the period
        $trainings = Training::query()
            ->whereHas('groups', fn ($q) => $q->where('groups.id', $group->id))
            ->where('evaluated', true)
            ->whereBetween('start', [$from, $to])
            ->orderBy('start')
            ->get(['id', 'start']);

        // current (active) members of the group
        $athletes = User::query()
            ->where('active', true)
            ->whereIn('id', DB::table('user_group')->where('group_id', $group->id)->select('user_id'))
            ->orderBy('familyName')->orderBy('firstName')
            ->get(['id', 'firstName', 'familyName']);

        // one query for all participations: [userId][trainingId] => [attend, cancelreason]
        $participations = [];
        if ($trainings->isNotEmpty() && $athletes->isNotEmpty()) {
            DB::table('training_participation')
                ->whereIn('training_id', $trainings->pluck('id'))
                ->whereIn('user_id', $athletes->pluck('id'))
                ->get(['user_id', 'training_id', 'attend', 'cancelreason'])
                ->each(function ($row) use (&$participations) {
                    $participations[$row->user_id][$row->training_id] = $row;
                });
        }

        $total = $trainings->count();
        $presentPerTraining = array_fill_keys($trainings->pluck('id')->all(), 0);

        $rows = $athletes->map(function (User $athlete) use ($trainings, $participations, $total, &$presentPerTraining) {
            $present = 0;
            $cells = $trainings->map(function (Training $training) use ($athlete, $participations, &$present, &$presentPerTraining) {
                $row = $participations[$athlete->id][$training->id] ?? null;
                if ($row === null) {
                    return ['status' => 'none', 'reason' => null];
                }
                if ((int) $row->attend === 1) {
                    $present++;
                    $presentPerTraining[$training->id]++;

                    return ['status' => 'present', 'reason' => null];
                }
                $reason = trim((string) $row->cancelreason);

                return $reason !== ''
                    ? ['status' => 'excused', 'reason' => $reason]
                    : ['status' => 'absent', 'reason' => null];
            })->values();

            return [
                'id' => $athlete->id,
                'firstName' => $athlete->firstName,
                'familyName' => $athlete->familyName,
                'presentCount' => $present,
                'rate' => $total > 0 ? (int) round($present / $total * 100) : null,
                'cells' => $cells,
            ];
        })->values();

        return response()->json([
            'group' => ['id' => $group->id, 'name' => $group->name],
            'from' => $data['from'],
            'to' => $data['to'],
            'trainings' => $trainings->map(fn (Training $t) => [
                'id' => $t->id,
                'start' => Carbon::parse($t->start)->format('Y-m-d\TH:i:s'),
                'presentCount' => $presentPerTraining[$t->id],
            ])->values(),
            'athletes' => $rows,
        ]);
    }
}
