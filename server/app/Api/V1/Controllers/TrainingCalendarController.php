<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrainingCalendar as TrainingCalendarResource;
use App\Http\Resources\TrainingCalendarSimple as TrainingCalendarResourceSimple;
use App\Training;

class TrainingCalendarController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:read-training', ['only' => ['getTrainings']]);

    }

    public function getTrainings()
    {
        $groupIds = request()->query('groupIds');
        $start = date(request()->query('start'));
        $end = date(request()->query('end'));

        return TrainingCalendarResource::collection($this->getTrainingsByTimeperiod($start, $end, $groupIds));
    }

    public function getSimpleTrainings()
    {
        $groupIds = request()->query('groupIds');
        $start = date(request()->query('start'));
        $end = date(request()->query('end'));

        return TrainingCalendarResourceSimple::collection($this->getTrainingsByTimeperiod($start, $end, $groupIds));
    }


    private function getTrainingsByTimeperiod($start, $end, $groupIds)
    {
        $day = null;

        if ($start == $end) {
            $day = $start;
        }

        return Training::when($day, function ($query) use ($day) {
            $query->whereDate('start', $day);
        })
            ->when(is_null($day), function ($query) use ($start, $end) {
                $query->whereBetween('start', array($start, $end));
            })
            ->when($groupIds, function ($query, $groupIds) {
                $query->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('group_id', preg_split('/,/', $groupIds));
                });
            })
            ->get();
    }

}
