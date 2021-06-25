<?php

namespace App\Console\Commands;

use App\Http\Controllers\ZoomController;
use App\Location;
use App\Training;
use Illuminate\Console\Command;
use App\TrainingSeries;
use DateTime;
use DateInterval;
use DatePeriod;
use Illuminate\Support\Facades\DB;

class TrainingSeriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'training:series';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all training series and add the training for the next 3 weeks';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //get all training series
        $allSeries = TrainingSeries::get();
        $start = new DateTime();
        $start->setTime(00, 00);
        $end = new DateTime();
        $end->setTime(00, 00);
        $end->modify('+7 day');
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($start, $interval, $end);
        $this->info('Creating training series in time period from ' . $start->format('Y-m-d H:i:s') . ' until ' . $end->format('Y-m-d H:i:s'));
        foreach ($allSeries as $series) {
            //create all possible trainings for next week
            $this->processSeries($series, $start, $end, $period);

        }

    }

    private function processSeries($series, $start, $end, $period)
    {
        $weekdaysArray = json_decode($series->weekdays);

        //iterate through the days of the next week
        foreach ($period as $dt) {
            $dayNumber = $dt->format("N");
            //check if series has this day
            if (in_array($dayNumber, $weekdaysArray)) {
                //check if series is deferred
                if ($series->defer_until && new DateTime($series->defer_until) >= new DateTime('midnight')) {
                    $this->info('Series with id ' . $series->id . ' is deferred until ' . $series->defer_until);
                    continue;
                }
                //check if training is already created
                if (DB::table('trainings')
                    ->whereBetween('start', array($start, $end))
                    ->where('training_series_id', $series->id)
                    ->exists()) {
                    $this->info('Training with series id ' . $series->id . ' already exists');
                    continue;
                }
                $this->info('Creating training with series id ' . $series->id);
                //create training from series data
                $training = new Training();
                $training->start = $this->toDateAddTime($dt->format(DateTime::ISO8601), $series->startTime);
                $training->end = $this->toDateAddTime($dt->format(DateTime::ISO8601), $series->endTime);
                $training->comment = $series->comment;
                $training->automatic_attend = $series->automatic_attend;
                $training->training_series_id = $series->id;
                $training->location_id = $series->location_id;
                $training->save();
                $training->contents()->attach($series->content_ids);
                $training->trainers()->attach($series->trainer_ids);
                $training->groups()->attach($series->group_ids);

                //is online training
                if ($training->location_id === Location::where('name', 'Online')->first()->id) {
                    $zoom = new ZoomController();
                    $zoom->updateOrCreate($training);
                }
        }
    }
}

private
function toDateAddTime($dateString, $timeString)
{
    $date = DateTime::createFromFormat(DateTime::ISO8601, $dateString);
    $time = DateTime::createFromFormat('H:i:s', $timeString);
    return $date->setTime($time->format("H"), $time->format("i"));
}
}
