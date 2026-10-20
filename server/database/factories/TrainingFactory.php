<?php

namespace Database\Factories;

use App\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Training>
 */
class TrainingFactory extends Factory
{
    protected $model = Training::class;

    public function definition(): array
    {
        return [
            'start' => now()->addDays(3)->setTime(18, 0)->format('Y-m-d H:i:s'),
            'end' => now()->addDays(3)->setTime(19, 30)->format('Y-m-d H:i:s'),
            'comment' => null,
            'prepared' => 0,
            'evaluated' => 0,
            'automatic_attend' => 0,
            'location_id' => null,
        ];
    }

    public function between(string $start, string $end): static
    {
        return $this->state(fn () => ['start' => $start, 'end' => $end]);
    }

    public function inPast(int $daysAgo = 3): static
    {
        return $this->between(
            now()->subDays($daysAgo)->setTime(18, 0)->format('Y-m-d H:i:s'),
            now()->subDays($daysAgo)->setTime(19, 30)->format('Y-m-d H:i:s'),
        );
    }

    public function forGroups(iterable $groups): static
    {
        return $this->afterCreating(function (Training $t) use ($groups) {
            $t->groups()->attach(collect($groups)->map(fn ($g) => is_object($g) ? $g->id : $g)->all());
        });
    }

    public function withTrainers(iterable $users): static
    {
        return $this->afterCreating(function (Training $t) use ($users) {
            $t->trainers()->attach(collect($users)->map(fn ($u) => is_object($u) ? $u->id : $u)->all());
        });
    }

    public function withContents(iterable $contents): static
    {
        return $this->afterCreating(function (Training $t) use ($contents) {
            $t->contents()->attach(collect($contents)->map(fn ($c) => is_object($c) ? $c->id : $c)->all());
        });
    }
}
