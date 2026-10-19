<?php

namespace Tests\Console;

use Database\Factories\GroupFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrainingAutomaticAttendCommandTest extends TestCase
{
    private function soon(int $hours = 5): TrainingFactory
    {
        return TrainingFactory::new()->between(
            now()->addHours($hours)->format('Y-m-d H:i:s'),
            now()->addHours($hours + 1)->format('Y-m-d H:i:s'),
        );
    }

    private function participation(int $trainingId, int $userId): ?object
    {
        return DB::table('training_participation')->where('training_id', $trainingId)->where('user_id', $userId)->first();
    }

    public function test_active_group_members_without_a_decision_are_checked_in(): void
    {
        $group = GroupFactory::new()->create();
        $member = UserFactory::new()->inGroups([$group])->create();
        $inactive = UserFactory::new()->inactive()->inGroups([$group])->create();
        $outsider = UserFactory::new()->inGroups([GroupFactory::new()->create()])->create();
        $training = $this->soon()->forGroups([$group])->create(['automatic_attend' => 1]);

        $this->artisan('training:automatic-attend')->assertSuccessful();

        $this->assertSame(1, (int) $this->participation($training->id, $member->id)->attend);
        $this->assertNull($this->participation($training->id, $inactive->id));
        $this->assertNull($this->participation($training->id, $outsider->id));
    }

    public function test_existing_decisions_are_not_touched(): void
    {
        $group = GroupFactory::new()->create();
        $declined = UserFactory::new()->inGroups([$group])->create();
        $training = $this->soon()->forGroups([$group])->create(['automatic_attend' => 1]);
        $training->participants()->attach($declined->id, ['attend' => 0, 'cancelreason' => 'krank']);

        $this->artisan('training:automatic-attend')->assertSuccessful();

        $row = $this->participation($training->id, $declined->id);
        $this->assertSame(0, (int) $row->attend);
        $this->assertSame('krank', $row->cancelreason);
        $this->assertSame(1, DB::table('training_participation')->count());
    }

    public function test_absent_members_are_registered_as_not_attending_with_a_reason(): void
    {
        $group = GroupFactory::new()->create();
        $absent = UserFactory::new()->inGroups([$group])->create([
            'absenceStart' => now()->subDay()->format('Y-m-d'),
            'absenceEnd' => now()->addDays(3)->format('Y-m-d'),
            'absenceReason' => 'Urlaub',
        ]);
        $training = $this->soon()->forGroups([$group])->create(['automatic_attend' => 1]);

        $this->artisan('training:automatic-attend')->assertSuccessful();

        $row = $this->participation($training->id, $absent->id);
        $this->assertSame(0, (int) $row->attend);
        $this->assertSame('[Abwesenheit bis '.now()->addDays(3)->format('Y-m-d').'] Urlaub', $row->cancelreason);
    }

    public function test_only_trainings_in_the_next_24_hours_with_the_flag_and_not_deleted_are_processed(): void
    {
        $group = GroupFactory::new()->create();
        $member = UserFactory::new()->inGroups([$group])->create();
        $tooFar = $this->soon(30)->forGroups([$group])->create(['automatic_attend' => 1]);
        $past = TrainingFactory::new()->inPast(1)->forGroups([$group])->create(['automatic_attend' => 1]);
        $noFlag = $this->soon()->forGroups([$group])->create(['automatic_attend' => 0]);
        $deleted = $this->soon()->forGroups([$group])->create(['automatic_attend' => 1, 'deleted' => 1]);

        $this->artisan('training:automatic-attend')->assertSuccessful();

        foreach ([$tooFar, $past, $noFlag, $deleted] as $training) {
            $this->assertNull($this->participation($training->id, $member->id), "training {$training->id} must be untouched");
        }
    }

    public function test_running_twice_does_not_duplicate_participations(): void
    {
        $group = GroupFactory::new()->create();
        UserFactory::new()->inGroups([$group])->create();
        $this->soon()->forGroups([$group])->create(['automatic_attend' => 1]);

        $this->artisan('training:automatic-attend')->assertSuccessful();
        $this->artisan('training:automatic-attend')->assertSuccessful();

        $this->assertSame(1, DB::table('training_participation')->count());
    }
}
