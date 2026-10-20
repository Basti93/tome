<?php

namespace Tests\Feature\Training;

use App\Training;
use App\TrainingTrainer;
use Database\Factories\TrainingFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class CompleteTrainingTest extends TestCase
{
    private function training(array $trainers, string $start = '2099-01-10 18:00:00', string $end = '2099-01-10 19:30:00'): Training
    {
        return TrainingFactory::new()->between($start, $end)->withTrainers($trainers)->create();
    }

    private function url(Training $t): string
    {
        return "/api/v1/trainingevaluation/{$t->id}/complete";
    }

    public function test_happy_path_sets_attendance_times_and_evaluated_in_one_call(): void
    {
        $trainer = $this->makeUser('trainer');
        $a = UserFactory::new()->create();
        $b = UserFactory::new()->create();
        $t = $this->training([$trainer]);
        $t->participants()->attach($a->id, ['attend' => 1]);

        $response = $this->actingAsJwt($trainer)->postJson($this->url($t), [
            'attendeeIds' => [$a->id, $b->id],
            'trainerTimes' => [['userId' => $trainer->id, 'start' => '2099-01-10T18:15:00+01:00', 'end' => '2099-01-10T19:00:00+01:00']],
        ])->assertOk();

        $response->assertJsonPath('data.id', $t->id)->assertJsonPath('data.evaluated', 1);
        $this->assertDatabaseHas('training_participation', ['training_id' => $t->id, 'user_id' => $a->id, 'attend' => 1]);
        $this->assertDatabaseHas('training_participation', ['training_id' => $t->id, 'user_id' => $b->id, 'attend' => 1]);
        $tt = TrainingTrainer::where('training_id', $t->id)->firstOrFail();
        $this->assertSame('2099-01-10 18:15:00', $tt->accounting_time_start->format('Y-m-d H:i:s'));
        $this->assertSame(45, $tt->accounting_minutes);
    }

    public function test_adding_a_person_who_is_not_a_group_member_creates_the_participation(): void
    {
        $trainer = $this->makeUser('trainer');
        $guest = UserFactory::new()->create();
        $t = $this->training([$trainer]);

        $this->actingAsJwt($trainer)->postJson($this->url($t), ['attendeeIds' => [$guest->id]])->assertOk();

        $this->assertDatabaseHas('training_participation', ['training_id' => $t->id, 'user_id' => $guest->id, 'attend' => 1]);
    }

    public function test_a_pre_registered_attendee_who_did_not_come_is_set_to_absent_not_deleted(): void
    {
        $trainer = $this->makeUser('trainer');
        $a = UserFactory::new()->create();
        $b = UserFactory::new()->create();
        $t = $this->training([$trainer]);
        $t->participants()->attach([$a->id => ['attend' => 1], $b->id => ['attend' => 1]]);

        $this->actingAsJwt($trainer)->postJson($this->url($t), ['attendeeIds' => [$a->id]])->assertOk();

        $this->assertDatabaseHas('training_participation', ['user_id' => $a->id, 'attend' => 1]);
        $this->assertDatabaseHas('training_participation', ['user_id' => $b->id, 'attend' => 0]);
        $this->assertDatabaseCount('training_participation', 2);
    }

    public function test_without_trainer_times_the_training_times_are_used(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = $this->training([$trainer]);

        $this->actingAsJwt($trainer)->postJson($this->url($t), ['attendeeIds' => []])->assertOk();

        $tt = TrainingTrainer::where('training_id', $t->id)->firstOrFail();
        $this->assertSame(90, $tt->accounting_minutes);
    }

    public function test_invalid_times_are_rejected_and_nothing_is_changed(): void
    {
        $trainer = $this->makeUser('trainer');
        $other = $this->makeUser('trainer');
        $a = UserFactory::new()->create();
        $t = $this->training([$trainer]);
        $good = ['userId' => $trainer->id, 'start' => '2099-01-10T18:00:00+01:00', 'end' => '2099-01-10T19:00:00+01:00'];

        foreach ([
            'end before start' => array_merge($good, ['end' => '2099-01-10T17:00:00+01:00']),
            'end equals start' => array_merge($good, ['end' => '2099-01-10T18:00:00+01:00']),
            'other day' => array_merge($good, ['end' => '2099-01-11T01:00:00+01:00']),
            'not a trainer of the training' => array_merge($good, ['userId' => $other->id]),
            'garbage' => array_merge($good, ['start' => 'xx']),
        ] as $label => $row) {
            $this->actingAsJwt($trainer)->postJson($this->url($t), ['attendeeIds' => [$a->id], 'trainerTimes' => [$row]])
                ->assertStatus(422, $label);
        }

        $this->assertDatabaseCount('training_participation', 0);
        $this->assertSame(0, (int) $t->fresh()->evaluated);
    }

    public function test_unknown_attendee_and_missing_attendee_list_are_422(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = $this->training([$trainer]);

        $this->actingAsJwt($trainer)->postJson($this->url($t), ['attendeeIds' => [999999]])->assertStatus(422);
        $this->actingAsJwt($trainer)->postJson($this->url($t), [])->assertStatus(422);
    }

    public function test_a_trainer_who_is_not_assigned_gets_403_and_nothing_changes(): void
    {
        $assigned = $this->makeUser('trainer');
        $stranger = $this->makeUser('trainer');
        $t = $this->training([$assigned]);

        $this->actingAsJwt($stranger)->postJson($this->url($t), ['attendeeIds' => []])->assertForbidden();
        $this->actingAsJwt($stranger)->getJson("/api/v1/trainingevaluation/{$t->id}/detail")->assertForbidden();

        $this->assertSame(0, (int) $t->fresh()->evaluated);
    }

    public function test_members_without_update_permission_get_403(): void
    {
        $member = $this->makeUser('member');
        $t = $this->training([$member]);

        $this->actingAsJwt($member)->postJson($this->url($t), ['attendeeIds' => []])->assertForbidden();
    }

    public function test_admin_may_complete_any_training(): void
    {
        $admin = $this->makeUser('admin');
        $trainer = $this->makeUser('trainer');
        $t = $this->training([$trainer]);

        $this->actingAsJwt($admin)->postJson($this->url($t), ['attendeeIds' => []])->assertOk();

        $this->assertSame(1, (int) $t->fresh()->evaluated);
    }

    public function test_deleted_and_unknown_trainings_are_404(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = $this->training([$trainer]);
        $t->forceFill(['deleted_at' => now()])->save();

        $this->actingAsJwt($trainer)->postJson($this->url($t), ['attendeeIds' => []])->assertNotFound();
        $this->actingAsJwt($trainer)->postJson('/api/v1/trainingevaluation/999999/complete', ['attendeeIds' => []])->assertNotFound();
    }

    public function test_completing_twice_is_idempotent(): void
    {
        $trainer = $this->makeUser('trainer');
        $a = UserFactory::new()->create();
        $t = $this->training([$trainer]);
        $payload = [
            'attendeeIds' => [$a->id],
            'trainerTimes' => [['userId' => $trainer->id, 'start' => '2099-01-10T18:00:00+01:00', 'end' => '2099-01-10T19:00:00+01:00']],
        ];

        $first = $this->actingAsJwt($trainer)->postJson($this->url($t), $payload)->assertOk()->json();
        $second = $this->actingAsJwt($trainer)->postJson($this->url($t), $payload)->assertOk()->json();

        $this->assertEquals($first, $second);
        $this->assertDatabaseCount('training_participation', 1);
        $this->assertSame(60, TrainingTrainer::where('training_id', $t->id)->firstOrFail()->accounting_minutes);
    }

    public function test_detail_returns_the_training_with_trainer_rows_and_participations(): void
    {
        $trainer = $this->makeUser('trainer');
        $a = UserFactory::new()->create();
        $t = $this->training([$trainer]);
        $t->participants()->attach($a->id, ['attend' => 1]);

        $this->actingAsJwt($trainer)->getJson("/api/v1/trainingevaluation/{$t->id}/detail")->assertOk()
            ->assertJsonPath('data.id', $t->id)
            ->assertJsonPath('data.trainers.0.userId', $trainer->id)
            ->assertJsonPath('data.participants.0.userId', $a->id);
    }

    public function test_accounting_times_are_returned_as_local_time_with_offset(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = $this->training([$trainer]);

        $this->actingAsJwt($trainer)->postJson($this->url($t), [
            'attendeeIds' => [],
            'trainerTimes' => [['userId' => $trainer->id, 'start' => '2099-01-10T18:15:00+01:00', 'end' => '2099-01-10T19:00:00+01:00']],
        ])->assertOk()
            ->assertJsonPath('data.trainers.0.accountingTimeStart', '2099-01-10T18:15:00+01:00')
            ->assertJsonPath('data.trainers.0.accountingTimeEnd', '2099-01-10T19:00:00+01:00');
    }
}
