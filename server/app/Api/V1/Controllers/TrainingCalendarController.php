<?php

namespace App\Api\V1\Controllers;

use App\Content;
use App\Group;
use App\Http\Controllers\Controller;
use App\Http\Resources\TrainingCalendar as TrainingCalendarResource;
use App\Http\Resources\TrainingCalendarSimple as TrainingCalendarResourceSimple;
use App\Training;
use App\TrainingSeries;
use App\User;
use Illuminate\Support\Facades\DB;
use DateTime;
use DateInterval;
use DatePeriod;
use Illuminate\Support\Facades\Log;

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
        $dateFormat = 'Y-m-d';
        $groupIds = request()->query('groupIds');
        $start = DateTime::createFromFormat($dateFormat, request()->query('start'));
        $end = DateTime::createFromFormat($dateFormat, request()->query('end'));

        return TrainingCalendarResource::collection($this->getTrainingsByTimeperiod($start, $end, $groupIds));
    }

    public function getSimpleTrainings()
    {
        $dateFormat = 'Y-m-d';
        $groupIds = request()->query('groupIds');
        $start = DateTime::createFromFormat($dateFormat, request()->query('start'));
        $end = DateTime::createFromFormat($dateFormat, request()->query('end'));

        return TrainingCalendarResourceSimple::collection($this->getTrainingsByTimeperiod($start, $end, $groupIds));
    }
    public function getPlannedTrainings()
    {
        $dateFormat = 'Y-m-d';
        $groupIds = request()->query('groupIds');
        $start = DateTime::createFromFormat($dateFormat, request()->query('start'));
        $end = DateTime::createFromFormat($dateFormat, request()->query('end'));

        return response()->json([
            'data' => $this->getPlannedTrainingsByTimeperiod($start, $end, $groupIds)
        ]);
    }


    private function getPlannedTrainingsByTimeperiod($start, $end, $groupIds) {
        $plannedTrainings = [];

        $nextWeek = new DateTime();
        $nextWeek->setTime(00,00);
        $nextWeek->modify('+7 day');
        //don' add series that are already created (trainings are created one week in advance)
        Log::info($end > $nextWeek);
        if ($end > $nextWeek) {
            Log::info('Creating also planned trainings');
            $allSeries = TrainingSeries::whereActive('1')
                ->when($groupIds, function ($query, $groupIds) {
                    $query->whereHas('groups', function ($query) use ($groupIds) {
                        $query->whereIn('group_id', preg_split('/,/', $groupIds));
                    });
                })
                ->get();

            $interval = DateInterval::createFromDateString('1 day');
            $today = new DateTime();
            $today->setTime(00,00);
            $periodStart = $start;
            if ($start < $today) {
                $periodStart = $nextWeek;
            }
            $period = new DatePeriod($periodStart, $interval, $end);

            foreach ($allSeries as $series) {
                $plannedTrainings = array_merge($plannedTrainings, $this->createTrainingsForSeries($series, $period));
            }
        }
        return $plannedTrainings;
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

    private function createTrainingsForSeries($series, $period) {
        $weekdaysArray = json_decode($series->weekdays);
        $result = [];

        //iterate through the days
        $tempId = (-$series->id) * 1000;
        foreach ($period as $dt) {

            $dayNumber = $dt->format("N");
            //check if series has this day

            if (in_array($dayNumber, $weekdaysArray)) {
                //create training from series data
                $training = new \stdClass();
                $training->id = $tempId;
                $training->start = $this->toDateAddTime($dt->format(DateTime::ISO8601), $series->startTime)->format(DateTime::ATOM);
                $training->end = $this->toDateAddTime($dt->format(DateTime::ISO8601), $series->endTime)->format(DateTime::ATOM);
                $training->comment = $series->comment;
                $training->training_series_id = $series->id;
                $training->location_id = $series->location_id;
                $training->group_ids = $series->group_ids;
                Log::info("after t : ".$training->group_ids);
                $training->trainer_ids = $series->trainer_ids;
                $training->content_ids = $series->content_ids;

                array_push($result, $training);
                $tempId = $tempId - 1;
            }
        }
        return $result;
    }

    private function toDateAddTime($dateString, $timeString) {
        $date = DateTime::createFromFormat(DateTime::ISO8601, $dateString);
        $time = DateTime::createFromFormat('H:i:s', $timeString);
        return $date->setTime($time->format("H"), $time->format("i"));

    }

}
