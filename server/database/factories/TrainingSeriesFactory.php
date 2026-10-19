<?php

namespace Database\Factories;

use App\TrainingSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSeries>
 */
class TrainingSeriesFactory extends Factory
{
    protected $model = TrainingSeries::class;

    public function definition(): array
    {
        return [
            'startTime' => '18:00:00',
            'endTime' => '19:30:00',
            'weekdays' => json_encode([1, 2, 3, 4, 5, 6, 7]),
            'comment' => null,
            'defer_until' => null,
            'automatic_attend' => 0,
            'location_id' => null,
        ];
    }

    /** @param  int[]  $days  ISO weekday numbers, 1 = Monday ... 7 = Sunday */
    public function onWeekdays(array $days): static
    {
        return $this->state(fn () => ['weekdays' => json_encode($days)]);
    }

    public function forGroups(iterable $groups): static
    {
        return $this->afterCreating(function (TrainingSeries $s) use ($groups) {
            $s->groups()->attach(collect($groups)->map(fn ($g) => is_object($g) ? $g->id : $g)->all());
        });
    }

    public function withTrainers(iterable $users): static
    {
        return $this->afterCreating(function (TrainingSeries $s) use ($users) {
            $s->trainers()->attach(collect($users)->map(fn ($u) => is_object($u) ? $u->id : $u)->all());
        });
    }
}
