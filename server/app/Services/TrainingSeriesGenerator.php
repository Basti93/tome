<?php

namespace App\Services;

use App\Training;
use App\TrainingSeries;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/** Creates the trainings of all training series for the next 7 days (skips days that already have one). */
class TrainingSeriesGenerator
{
    /** @return int number of created trainings */
    public function generate(): int
    {
        $start = new DateTime('today');
        $end = new DateTime('today');
        $end->modify('+7 day');
        $period = new DatePeriod($start, DateInterval::createFromDateString('1 day'), $end);

        $created = 0;
        foreach (TrainingSeries::get() as $series) {
            $created += $this->generateForSeries($series, $period);
        }

        return $created;
    }

    private function generateForSeries(TrainingSeries $series, DatePeriod $period): int
    {
        // a corrupt series must not stop the creation of all the other series
        if (! is_array($series->weekdays)) {
            return 0;
        }
        // deferred series create nothing
        if ($series->defer_until && new DateTime($series->defer_until) >= new DateTime('midnight')) {
            return 0;
        }

        $created = 0;
        foreach ($period as $day) {
            if (! in_array($day->format('N'), $series->weekdays)) {
                continue;
            }
            // raw query on purpose: trainings that were deleted in the future still count as created
            $exists = DB::table('trainings')
                ->whereDate('start', $day->format('Y-m-d'))
                ->where('training_series_id', $series->id)
                ->exists();
            if ($exists) {
                continue;
            }

            $training = new Training;
            $training->start = $this->atTime($day, $series->startTime);
            $training->end = $this->atTime($day, $series->endTime);
            $training->comment = $series->comment;
            $training->automatic_attend = $series->automatic_attend;
            $training->training_series_id = $series->id;
            $training->location_id = $series->location_id;
            $training->save();
            $training->contents()->attach($series->content_ids);
            $training->trainers()->attach($series->trainer_ids);
            $training->groups()->attach($series->group_ids);
            $created++;
        }

        return $created;
    }

    private function atTime(DateTimeInterface $day, string $time): string
    {
        [$hour, $minute] = explode(':', $time);

        return DateTimeImmutable::createFromInterface($day)->setTime((int) $hour, (int) $minute)->format('Y-m-d H:i:s');
    }
}
