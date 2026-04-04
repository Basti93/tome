<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Training;
use App\TrainingSeries;
use App\Group;
use App\Content;
use App\User;
use App\Location;
use Carbon\Carbon;

class TrainingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $location = Location::where('name', 'Gym A')->first();
        $series = TrainingSeries::where('comment', 'Weekly women\'s training series')->first();
        $group = Group::where('name', 'Wettkampfturner I')->first();
        $trainer = User::where('email', 'trainer@tome.local')->first();
        $member = User::where('email', 'member@tome.local')->first();
        $contents = Content::whereIn('name', ['Boden', 'Sprung'])->get();

        if (!$group || !$trainer || !$member || $contents->isEmpty()) {
            return;
        }

        $start = Carbon::now()->addDays(3)->setTime(18, 0, 0);
        $end = (clone $start)->addMinutes(90);

        $training = Training::firstOrNew([
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
        ]);

        $training->comment = 'First seeded training block';
        $training->prepared = 1;
        $training->evaluated = 0;
        $training->location_id = optional($location)->id;
        $training->training_series_id = optional($series)->id;
        $training->save();

        $training->groups()->syncWithoutDetaching([$group->id]);
        $training->trainers()->syncWithoutDetaching([$trainer->id]);
        $training->contents()->syncWithoutDetaching($contents->pluck('id')->all());
        $training->participants()->syncWithoutDetaching([$member->id => ['attend' => 1]]);
    }
}
