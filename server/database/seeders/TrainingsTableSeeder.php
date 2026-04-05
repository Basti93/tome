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
        $locations = Location::all();
        $groups = Group::all();
        $trainers = User::whereHas('roles', fn($q) => $q->where('name', 'trainer'))->get();
        $members = User::whereHas('roles', fn($q) => $q->where('name', 'member'))->get();
        $contents = Content::all();

        if ($locations->isEmpty() || $groups->isEmpty() || $trainers->isEmpty() || $contents->isEmpty()) {
            return;
        }

        // Create 30 past trainings for evaluation (from last 30 days)
        for ($i = 0; $i < 30; $i++) {
            $daysOffset = floor($i / 4); // 4 trainings per day
            $timeSlot = $i % 4;

            $times = [
                ['hour' => 9, 'minute' => 0],
                ['hour' => 14, 'minute' => 0],
                ['hour' => 17, 'minute' => 0],
                ['hour' => 19, 'minute' => 0],
            ];

            $time = $times[$timeSlot];
            $start = Carbon::now()->subDays(30 - $daysOffset)->setTime($time['hour'], $time['minute'], 0);
            $end = (clone $start)->addMinutes(90);

            $training = new Training([
                'start' => $start->toDateTimeString(),
                'end' => $end->toDateTimeString(),
                'comment' => 'Past training ' . ($i + 1),
                'prepared' => 1,
                'evaluated' => rand(0, 1), // Some already evaluated, some pending
                'location_id' => $locations->random()->id,
            ]);
            $training->save();

            // Assign random group(s)
            $randomGroups = $groups->random(rand(1, 2))->pluck('id')->all();
            $training->groups()->syncWithoutDetaching($randomGroups);

            // Assign random trainer(s)
            $randomTrainers = $trainers->random(rand(1, 2))->pluck('id')->all();
            $training->trainers()->syncWithoutDetaching($randomTrainers);

            // Assign random contents
            if ($contents->isNotEmpty()) {
                $randomContents = $contents->random(rand(1, 3))->pluck('id')->all();
                $training->contents()->syncWithoutDetaching($randomContents);
            }

            // Assign random members as participants
            $participantIds = [];
            foreach ($members->random(rand(3, min(8, $members->count()))) as $member) {
                $participantIds[$member->id] = ['attend' => rand(0, 1)];
            }
            if (!empty($participantIds)) {
                $training->participants()->syncWithoutDetaching($participantIds);
            }
        }

        // Create 100 future trainings spread across the next 60 days
        for ($i = 0; $i < 100; $i++) {
            // Spread trainings across different days and times
            $daysOffset = floor($i / 4); // 4 trainings per day
            $timeSlot = $i % 4; // Morning, midday, afternoon, evening

            $times = [
                ['hour' => 9, 'minute' => 0],
                ['hour' => 14, 'minute' => 0],
                ['hour' => 17, 'minute' => 0],
                ['hour' => 19, 'minute' => 0],
            ];

            $time = $times[$timeSlot];
            $start = Carbon::now()->addDays(3 + $daysOffset)->setTime($time['hour'], $time['minute'], 0);
            $end = (clone $start)->addMinutes(90);

            $training = new Training([
                'start' => $start->toDateTimeString(),
                'end' => $end->toDateTimeString(),
                'comment' => 'Seeded training ' . ($i + 1),
                'prepared' => rand(0, 1),
                'evaluated' => 0, // Future trainings not yet evaluated
                'location_id' => $locations->random()->id,
            ]);
            $training->save();

            // Assign random group(s)
            $randomGroups = $groups->random(rand(1, 2))->pluck('id')->all();
            $training->groups()->syncWithoutDetaching($randomGroups);

            // Assign random trainer(s)
            $randomTrainers = $trainers->random(rand(1, 2))->pluck('id')->all();
            $training->trainers()->syncWithoutDetaching($randomTrainers);

            // Assign random contents
            if ($contents->isNotEmpty()) {
                $randomContents = $contents->random(rand(1, 3))->pluck('id')->all();
                $training->contents()->syncWithoutDetaching($randomContents);
            }

            // Assign random members as participants
            $participantIds = [];
            foreach ($members->random(rand(3, min(8, $members->count()))) as $member) {
                $participantIds[$member->id] = ['attend' => rand(0, 1)];
            }
            if (!empty($participantIds)) {
                $training->participants()->syncWithoutDetaching($participantIds);
            }
        }
    }
}
