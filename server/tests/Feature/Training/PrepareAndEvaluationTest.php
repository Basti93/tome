<?php

namespace Tests\Feature\Training;

use App\TrainingTrainer;
use Database\Factories\ContentFactory;
use Database\Factories\GroupFactory;
use Database\Factories\LocationFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PrepareAndEvaluationTest extends TestCase
{
    public function test_prepare_lists_the_next_five_upcoming_trainings_of_a_trainer(): void
    {
        $trainer = $this->makeUser('trainer');
        $other = $this->makeUser('trainer');
        $mine = [];
        for ($i = 1; $i <= 6; $i++) {
            $mine[] = TrainingFactory::new()->between(now()->addDays($i + 1)->format('Y-m-d 18:00:00'), now()->addDays($i + 1)->format('Y-m-d 19:00:00'))
                ->withTrainers([$trainer])->create();
        }
        TrainingFactory::new()->withTrainers([$other])->create();
        TrainingFactory::new()->inPast()->withTrainers([$trainer])->create();
        TrainingFactory::new()->withTrainers([$trainer])->create(['deleted' => 1]);

        $response = $this->actingAsJwt($trainer)->getJson("/api/v1/trainingprepare/{$trainer->id}")->assertOk();

        $this->assertSame(collect($mine)->take(5)->pluck('id')->all(), collect($response->json('data'))->pluck('id')->all());
        $response->assertJsonStructure(['data' => [['id', 'start', 'end', 'locationId', 'groupIds', 'trainerIds', 'participants', 'contentIds', 'comment', 'prepared', 'evaluated', 'automaticAttend']]]);
    }

    public function test_prepared_flag_and_field_updates(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = TrainingFactory::new()->create();
        $location = LocationFactory::new()->create();
        $content = ContentFactory::new()->create();

        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingprepare/{$t->id}/updatelocation", ['locationId' => $location->id])->assertOk();
        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingprepare/{$t->id}/updatecomment", ['comment' => 'Neu'])->assertOk();
        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingprepare/{$t->id}/updatecontent", ['contentIds' => [$content->id]])->assertOk();
        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingprepare/{$t->id}/updatetrainingtime", [
            'start' => '2099-10-01T17:00:00+0200', 'end' => '2099-10-01T18:00:00+0200',
        ])->assertOk();

        $t->refresh();
        $this->assertSame($location->id, $t->location_id);
        $this->assertSame('Neu', $t->comment);
        $this->assertSame([$content->id], $t->contents()->pluck('contents.id')->all());
        $this->assertSame('2099-10-01 17:00:00', $t->start);
        $this->assertSame('2099-10-01 18:00:00', $t->end);
    }

    public function test_prepared_route_of_trainingprepare_is_broken(): void
    {
        // BUG: POST trainingprepare/{id}/prepared is routed to TrainingPrepareController@trainingEvaluated,
        // a method that does not exist (the controller only has trainingPrepared), so it answers 500.
        // The working endpoint is POST training/{id}/prepared (TrainingController).
        $trainer = $this->makeUser('trainer');
        $t = TrainingFactory::new()->create();

        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingprepare/{$t->id}/prepared")->assertStatus(500);
        $this->assertSame(0, (int) $t->fresh()->prepared);
    }

    public function test_prepare_endpoints_404_for_unknown_training(): void
    {
        $trainer = $this->makeUser('trainer');

        $this->actingAsJwt($trainer)->postJson('/api/v1/trainingprepare/999999/updatecomment', ['comment' => 'x'])->assertNotFound();
    }

    public function test_evaluation_lists_the_last_five_past_trainings_with_trainer_rows(): void
    {
        $trainer = $this->makeUser('trainer');
        $past = TrainingFactory::new()->inPast(2)->withTrainers([$trainer])->create();
        TrainingFactory::new()->withTrainers([$trainer])->create(); // upcoming, excluded

        $response = $this->actingAsJwt($trainer)->getJson("/api/v1/trainingevaluation/{$trainer->id}")->assertOk();

        $response->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $past->id);
        $this->assertSame(
            ['id', 'start', 'end', 'locationId', 'groupIds', 'trainers', 'participants', 'contentIds', 'comment', 'prepared', 'evaluated'],
            array_keys($response->json('data.0'))
        );
        $response->assertJsonPath('data.0.trainers.0.userId', $trainer->id)
            ->assertJsonPath('data.0.trainers.0.trainingId', $past->id)
            ->assertJsonPath('data.0.trainers.0.accountingTimeStart', null);
    }

    public function test_evaluated_sets_flag_and_defaults_accounting_times_to_the_training_times(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = TrainingFactory::new()->between('2099-01-10 18:00:00', '2099-01-10 19:30:00')->withTrainers([$trainer])->create();

        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/evaluated")
            ->assertOk()->assertExactJson(['status' => 'ok']);

        $this->assertSame(1, (int) $t->fresh()->evaluated);
        $tt = TrainingTrainer::where('training_id', $t->id)->firstOrFail();
        $this->assertSame('2099-01-10 18:00:00', $tt->accounting_time_start->format('Y-m-d H:i:s'));
        $this->assertSame('2099-01-10 19:30:00', $tt->accounting_time_end->format('Y-m-d H:i:s'));
        $this->assertSame(90, $tt->accounting_minutes);
        $this->assertEquals(2.0, $tt->accounting_hours, '1 accounting hour = 45 minutes');
    }

    public function test_evaluated_keeps_manually_set_accounting_times(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = TrainingFactory::new()->between('2099-01-10 18:00:00', '2099-01-10 19:30:00')->withTrainers([$trainer])->create();

        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/updateaccountingtime", [
            'trainerId' => $trainer->id,
            'start' => '2099-01-10T18:00:00+0100',
            'end' => '2099-01-10T18:45:00+0100',
        ])->assertOk();
        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/evaluated")->assertOk();

        $tt = TrainingTrainer::where('training_id', $t->id)->firstOrFail();
        $this->assertSame(45, $tt->accounting_minutes);
        $this->assertEquals(1.0, $tt->accounting_hours);
    }

    public function test_add_and_remove_participant(): void
    {
        $trainer = $this->makeUser('trainer');
        $user = UserFactory::new()->create();
        $t = TrainingFactory::new()->create();

        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/addparticipant/{$user->id}")
            ->assertOk()->assertExactJson(['status' => 'ok']);
        $this->assertDatabaseHas('training_participation', ['training_id' => $t->id, 'user_id' => $user->id, 'attend' => 1]);

        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/removeparticipant/{$user->id}")
            ->assertOk()->assertExactJson(['status' => 'ok']);
        $this->assertDatabaseHas('training_participation', ['training_id' => $t->id, 'user_id' => $user->id, 'attend' => 0]);

        // adding again flips the existing row back instead of duplicating it
        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/addparticipant/{$user->id}")->assertOk();
        $this->assertDatabaseCount('training_participation', 1);
        $this->assertDatabaseHas('training_participation', ['user_id' => $user->id, 'attend' => 1]);
    }

    public function test_remove_participant_who_never_participated_is_404(): void
    {
        $trainer = $this->makeUser('trainer');
        $user = UserFactory::new()->create();
        $t = TrainingFactory::new()->create();

        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/removeparticipant/{$user->id}")
            ->assertNotFound()->assertExactJson(['status' => 'user not assigned to the training']);
    }

    public function test_accounting_time_statistics_sum_hours_per_trainer_and_month(): void
    {
        Cache::flush();
        $trainer = $this->makeUser('trainer');
        $g = GroupFactory::new()->create();
        $jan = TrainingFactory::new()->between('2099-01-10 18:00:00', '2099-01-10 19:30:00')->forGroups([$g])->withTrainers([$trainer])->create(['evaluated' => 1]);
        $jan2 = TrainingFactory::new()->between('2099-01-17 18:00:00', '2099-01-17 18:45:00')->forGroups([$g])->withTrainers([$trainer])->create(['evaluated' => 1]);
        $feb = TrainingFactory::new()->between('2099-02-07 18:00:00', '2099-02-07 19:30:00')->forGroups([$g])->withTrainers([$trainer])->create(['evaluated' => 1]);
        TrainingFactory::new()->between('2099-02-14 18:00:00', '2099-02-14 19:30:00')->forGroups([$g])->withTrainers([$trainer])->create(['evaluated' => 0]);

        foreach ([$jan, $jan2, $feb] as $t) {
            $this->actingAsJwt($trainer)->postJson("/api/v1/trainingevaluation/{$t->id}/evaluated")->assertOk();
        }

        $response = $this->actingAsJwt($trainer)->getJson("/api/v1/trainingevaluation/accountingtimestatistics?year=2099&groupIds={$g->id}")->assertOk();

        $response->assertJsonCount(1);
        $response->assertJsonPath('0.trainer.id', $trainer->id);
        $this->assertEquals([
            ['month' => 1, 'accountingHours' => 3.0],
            ['month' => 2, 'accountingHours' => 2.0],
        ], $response->json('0.data'));
    }

    public function test_accounting_time_statistics_are_cached(): void
    {
        Cache::flush();
        $trainer = $this->makeUser('trainer');

        $this->actingAsJwt($trainer)->getJson('/api/v1/trainingevaluation/accountingtimestatistics?year=2098')->assertOk()->assertExactJson([]);
        $this->assertTrue(Cache::has('accounting_time_statistics2098'));
    }

    public function test_export_accounting_times_stores_an_xls_file(): void
    {
        Excel::fake();
        $trainer = $this->makeUser('trainer', ['firstName' => 'Tina', 'familyName' => 'Trainer']);

        $this->actingAsJwt($trainer)->postJson('/api/v1/trainingevaluation/exportaccountingtimes', [
            'userId' => $trainer->id,
            'from' => '2099-01-01T00:00:00+0100',
            'to' => '2099-01-31T00:00:00+0100',
        ])->assertOk()->assertExactJson([
            'status' => 'ok',
            'fileName' => '/accounting_times/ul_abrechnung_tina_trainer_01_01_2099_31_01_2099.xls',
        ]);

        Excel::assertStored('/accounting_times/ul_abrechnung_tina_trainer_01_01_2099_31_01_2099.xls', 'public');
    }

    public function test_export_accounting_times_validates_input(): void
    {
        $trainer = $this->makeUser('trainer');

        $this->actingAsJwt($trainer)->postJson('/api/v1/trainingevaluation/exportaccountingtimes', [])
            ->assertStatus(422)->assertJsonValidationErrors(['userId', 'from', 'to']);
    }
}
