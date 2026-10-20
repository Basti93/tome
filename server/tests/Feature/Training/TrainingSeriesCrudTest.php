<?php

namespace Tests\Feature\Training;

use App\Training;
use App\TrainingSeries;
use Database\Factories\ContentFactory;
use Database\Factories\GroupFactory;
use Database\Factories\LocationFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\TrainingSeriesFactory;
use Tests\TestCase;

class TrainingSeriesCrudTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'startTime' => '18:00:00',
            'endTime' => '19:30:00',
            'weekdays' => [1, 3],
            'comment' => 'Serie',
            'automaticAttend' => 'true',
            'trainerIds' => [],
            'groupIds' => [],
            'contentIds' => [],
            'deferUntil' => '2099-12-31T00:00:00+0100',
        ], $overrides);
    }

    public function test_store_creates_series_with_relations(): void
    {
        $trainer = $this->makeUser('trainer');
        $group = GroupFactory::new()->create();
        $content = ContentFactory::new()->create();
        $location = LocationFactory::new()->create();

        $this->actingAsJwt($trainer)->postJson('/api/v1/trainingSeries', $this->payload([
            'locationId' => $location->id,
            'trainerIds' => [$trainer->id],
            'groupIds' => [$group->id],
            'contentIds' => [$content->id],
        ]))->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $series = TrainingSeries::firstOrFail();
        $this->assertSame('18:00:00', $series->startTime);
        $this->assertSame([1, 3], $series->weekdays);
        $this->assertSame('Serie', $series->comment);
        $this->assertSame(1, (int) $series->automatic_attend);
        $this->assertSame('2099-12-31 00:00:00', $series->defer_until);
        $this->assertSame($location->id, $series->location_id);
        $this->assertSame([$trainer->id], $series->trainers()->pluck('users.id')->all());
        $this->assertSame([$group->id], $series->groups()->pluck('groups.id')->all());
        $this->assertSame([$content->id], $series->contents()->pluck('contents.id')->all());
        $this->assertSame(0, Training::count(), 'a deferred series does not create trainings');
    }

    public function test_store_runs_the_series_command_and_creates_trainings(): void
    {
        $trainer = $this->makeUser('trainer');
        $group = GroupFactory::new()->create();

        $this->actingAsJwt($trainer)->postJson('/api/v1/trainingSeries', $this->payload([
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'groupIds' => [$group->id],
            'trainerIds' => [$trainer->id],
            'deferUntil' => null,
        ]))->assertStatus(201);

        $training = Training::firstOrFail();
        $this->assertSame(TrainingSeries::firstOrFail()->id, (int) $training->training_series_id);
        $this->assertSame([$group->id], $training->groups()->pluck('groups.id')->all());
        $this->assertSame([$trainer->id], $training->trainers()->pluck('users.id')->all());
    }

    public function test_store_validates_required_fields(): void
    {
        $trainer = $this->makeUser('trainer');

        $this->actingAsJwt($trainer)->postJson('/api/v1/trainingSeries', [])
            ->assertStatus(422)->assertJsonValidationErrors(['startTime', 'endTime', 'weekdays']);
    }

    public function test_update_overwrites_the_series_and_its_relations(): void
    {
        $trainer = $this->makeUser('trainer');
        $g1 = GroupFactory::new()->create();
        $g2 = GroupFactory::new()->create();
        $series = TrainingSeriesFactory::new()->forGroups([$g1])->create(['defer_until' => '2099-12-31 00:00:00']);

        $this->actingAsJwt($trainer)->putJson("/api/v1/trainingSeries/{$series->id}", $this->payload([
            'comment' => 'neu', 'groupIds' => [$g2->id], 'weekdays' => [5],
        ]))->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $series->refresh();
        $this->assertSame('neu', $series->comment);
        $this->assertSame([5], $series->weekdays);
        $this->assertSame([$g2->id], $series->groups()->pluck('groups.id')->all());
    }

    public function test_update_unknown_series_is_404(): void
    {
        $trainer = $this->makeUser('trainer');

        $this->actingAsJwt($trainer)->putJson('/api/v1/trainingSeries/999999', $this->payload())->assertNotFound();
    }

    public function test_destroy_deletes_the_series_but_keeps_generated_trainings(): void
    {
        $trainer = $this->makeUser('trainer');
        $series = TrainingSeriesFactory::new()->create();
        $training = TrainingFactory::new()->create(['training_series_id' => $series->id]);

        $this->actingAsJwt($trainer)->deleteJson("/api/v1/trainingSeries/{$series->id}")
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $this->assertDatabaseMissing('training_series', ['id' => $series->id]);
        $this->assertDatabaseHas('trainings', ['id' => $training->id, 'training_series_id' => null]);
    }

    public function test_store_rejects_weekdays_that_are_not_a_list_of_weekday_numbers(): void
    {
        $trainer = $this->makeUser('trainer');

        // a DOM Event object that used to leak into the payload came out as {"isTrusted":true}
        $this->actingAsJwt($trainer)->postJson('/api/v1/trainingSeries', $this->payload(['weekdays' => ['isTrusted' => true]]))
            ->assertStatus(422)->assertJsonValidationErrors(['weekdays']);
        $this->actingAsJwt($trainer)->postJson('/api/v1/trainingSeries', $this->payload(['weekdays' => []]))
            ->assertStatus(422)->assertJsonValidationErrors(['weekdays']);
        $this->actingAsJwt($trainer)->postJson('/api/v1/trainingSeries', $this->payload(['weekdays' => [8]]))
            ->assertStatus(422);
        $this->assertSame(0, TrainingSeries::count());
    }

    public function test_series_command_skips_a_series_with_corrupt_weekdays(): void
    {
        $bad = TrainingSeriesFactory::new()->create(['weekdays' => '{"isTrusted":true}']);

        $this->artisan('training:series')->assertSuccessful();

        $this->assertSame(0, Training::where('training_series_id', $bad->id)->count());
    }

    public function test_the_exact_payload_of_the_vuetify_4_dialog_is_stored_as_a_json_list(): void
    {
        $trainer = $this->makeUser('trainer');
        $group = GroupFactory::new()->create();

        // as sent by TrainingSeriesPage.save(): times without seconds, nulls, the id repeated in the body
        $this->actingAsJwt($trainer)->postJson('/api/v1/trainingSeries', [
            'startTime' => '09:00', 'endTime' => '12:00', 'weekdays' => [1, 3], 'comment' => null,
            'locationId' => null, 'trainerIds' => [], 'groupIds' => [$group->id], 'contentIds' => [],
            'deferUntil' => null, 'automaticAttend' => true,
        ])->assertStatus(201);

        $series = TrainingSeries::firstOrFail();
        $this->assertSame([1, 3], $series->weekdays);

        $this->actingAsJwt($trainer)->putJson("/api/v1/trainingSeries/{$series->id}", [
            'id' => $series->id, 'startTime' => '09:00', 'endTime' => '12:00', 'weekdays' => [2, 4], 'comment' => 'x',
            'locationId' => null, 'trainerIds' => [], 'groupIds' => [$group->id], 'contentIds' => [],
            'deferUntil' => null, 'automaticAttend' => false,
        ])->assertStatus(201);

        $this->assertSame([2, 4], $series->fresh()->weekdays);
    }
}
