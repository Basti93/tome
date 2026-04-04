<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\TrainingSeries;
use App\Group;
use App\Content;
use App\User;
use App\Location;

class TrainingSeriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $location = Location::where('name', 'Main Hall')->first();
        $group = Group::where('name', 'Wettkampfturner I')->first();
        $trainer = User::where('email', 'trainer@tome.local')->first();
        $contents = Content::whereIn('name', ['Boden', 'Sprung', 'Schwebebalken'])->get();

        if (!$group || !$trainer || $contents->isEmpty()) {
            return;
        }

        $series = TrainingSeries::firstOrNew([
            'comment' => "Weekly women's training series",
            'location_id' => optional($location)->id,
        ]);

        $series->startTime = '18:00:00';
        $series->endTime = '19:30:00';
        $series->weekdays = json_encode(['Mon', 'Wed']);
        $series->defer_until = null;
        $series->save();

        $series->groups()->syncWithoutDetaching([$group->id]);
        $series->trainers()->syncWithoutDetaching([$trainer->id]);
        $series->contents()->syncWithoutDetaching($contents->pluck('id')->all());
    }
}
