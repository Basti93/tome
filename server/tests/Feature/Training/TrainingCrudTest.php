<?php

namespace Tests\Feature\Training;

use App\Training;
use Database\Factories\ContentFactory;
use Database\Factories\GroupFactory;
use Database\Factories\LocationFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class TrainingCrudTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'start' => '2099-09-01T18:00:00+0200',
            'end' => '2099-09-01T19:30:00+0200',
            'comment' => 'Sparring',
            'automaticAttend' => 'true',
            'trainerIds' => [],
            'groupIds' => [],
        ], $overrides);
    }

    public function test_index_is_paginated_newest_first_and_hides_deleted(): void
    {
        $trainer = $this->makeUser('trainer');
        $old = TrainingFactory::new()->between('2099-01-01 10:00:00', '2099-01-01 11:00:00')->create();
        $new = TrainingFactory::new()->between('2099-02-01 10:00:00', '2099-02-01 11:00:00')->create();
        TrainingFactory::new()->create(['deleted' => 1]);

        $response = $this->actingAsJwt($trainer)->getJson('/api/v1/training')->assertOk();

        $this->assertSame([$new->id, $old->id], collect($response->json('data'))->pluck('id')->all());
        $response->assertJsonPath('meta.perPage', 10)->assertJsonPath('meta.total', 2);
    }

    public function test_index_filters_by_group_and_honors_per_page(): void
    {
        $trainer = $this->makeUser('trainer');
        $g = GroupFactory::new()->create();
        $inGroup = TrainingFactory::new()->forGroups([$g])->create();
        TrainingFactory::new()->count(2)->create();

        $ids = collect($this->actingAsJwt($trainer)->getJson("/api/v1/training?groupIds={$g->id}")->json('data'))->pluck('id')->all();
        $this->assertSame([$inGroup->id], $ids);

        $this->actingAsJwt($trainer)->getJson('/api/v1/training?per_page=2')->assertJsonCount(2, 'data')->assertJsonPath('meta.lastPage', 2);
    }

    public function test_sort_maps_date_and_location_columns(): void
    {
        $trainer = $this->makeUser('trainer');
        $a = TrainingFactory::new()->between('2099-01-01 10:00:00', '2099-01-01 11:00:00')->create();
        $b = TrainingFactory::new()->between('2099-03-01 10:00:00', '2099-03-01 11:00:00')->create();

        $asc = collect($this->actingAsJwt($trainer)->getJson('/api/v1/training/sort?sortBy=date&direction=asc')->assertOk()->json('data'))->pluck('id')->all();
        $desc = collect($this->actingAsJwt($trainer)->getJson('/api/v1/training/sort?sortBy=date&direction=desc')->json('data'))->pluck('id')->all();

        $this->assertSame([$a->id, $b->id], $asc);
        $this->assertSame([$b->id, $a->id], $desc);

        $this->actingAsJwt($trainer)->getJson('/api/v1/training/sort?sortBy=locationId&direction=asc')->assertOk();
    }

    public function test_get_by_id(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = TrainingFactory::new()->create(['comment' => 'hello']);

        $this->actingAsJwt($trainer)->getJson("/api/v1/training/{$t->id}")->assertOk()
            ->assertJsonPath('data.id', $t->id)
            ->assertJsonPath('data.comment', 'hello');
        $this->actingAsJwt($trainer)->getJson('/api/v1/training/999999')->assertNotFound();
    }

    public function test_calendar_requires_read_permission_and_exposes_participants(): void
    {
        $member = $this->makeUser('member');
        $t = TrainingFactory::new()->between('2099-06-10 18:00:00', '2099-06-10 19:00:00')->create();

        $response = $this->actingAsJwt($member)->getJson('/api/v1/training/calendar?start=2099-06-01&end=2099-06-30')->assertOk();

        $response->assertJsonPath('data.0.id', $t->id);
        $this->assertSame(
            ['id', 'start', 'end', 'locationId', 'groupIds', 'trainerIds', 'participants', 'contentIds', 'comment', 'prepared', 'evaluated'],
            array_keys($response->json('data.0'))
        );
    }

    public function test_store_creates_training_with_relations_and_participants(): void
    {
        $trainer = $this->makeUser('trainer');
        $group = GroupFactory::new()->create();
        $location = LocationFactory::new()->create();
        $content = ContentFactory::new()->create();
        $p1 = UserFactory::new()->inGroups([$group])->create();

        $response = $this->actingAsJwt($trainer)->postJson('/api/v1/training', $this->payload([
            'locationId' => $location->id,
            'trainerIds' => [$trainer->id],
            'groupIds' => [$group->id],
            'contentIds' => [$content->id],
            'participantIds' => [$p1->id],
        ]))->assertStatus(201)->assertJsonPath('status', 'ok');

        // the response echoes the start formatted as a UTC-looking string without converting the offset
        $response->assertJsonPath('start', '2099-09-01T18:00:00.000Z');

        $training = Training::firstOrFail();
        $this->assertSame('2099-09-01 18:00:00', $training->start);
        $this->assertSame('2099-09-01 19:30:00', $training->end);
        $this->assertSame('Sparring', $training->comment);
        $this->assertSame(1, (int) $training->automatic_attend);
        $this->assertSame($location->id, $training->location_id);
        $this->assertSame([$trainer->id], $training->trainers()->pluck('users.id')->all());
        $this->assertSame([$group->id], $training->groups()->pluck('groups.id')->all());
        $this->assertSame([$content->id], $training->contents()->pluck('contents.id')->all());
        $this->assertDatabaseHas('training_participation', ['training_id' => $training->id, 'user_id' => $p1->id, 'attend' => 1]);
    }

    public function test_store_without_automatic_attend_flag_defaults_to_zero(): void
    {
        $trainer = $this->makeUser('trainer');

        $this->actingAsJwt($trainer)->postJson('/api/v1/training', $this->payload(['automaticAttend' => 'false']))->assertStatus(201);

        $this->assertSame(0, (int) Training::firstOrFail()->automatic_attend);
    }

    public function test_store_validates_start_and_end(): void
    {
        $trainer = $this->makeUser('trainer');

        $this->actingAsJwt($trainer)->postJson('/api/v1/training', [])
            ->assertStatus(422)->assertJsonValidationErrors(['start', 'end']);
    }

    public function test_update_replaces_relations_and_marks_unlisted_participants_as_not_attending(): void
    {
        $trainer = $this->makeUser('trainer');
        $g1 = GroupFactory::new()->create();
        $g2 = GroupFactory::new()->create();
        $keep = UserFactory::new()->create();
        $drop = UserFactory::new()->create();
        $t = TrainingFactory::new()->forGroups([$g1])->create();
        $t->participants()->attach($keep->id, ['attend' => 1]);
        $t->participants()->attach($drop->id, ['attend' => 1]);

        $this->actingAsJwt($trainer)->putJson("/api/v1/training/{$t->id}", $this->payload([
            'comment' => 'changed',
            'groupIds' => [$g2->id],
            'trainerIds' => [$trainer->id],
            'participantIds' => [$keep->id],
        ]))->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $t->refresh();
        $this->assertSame('changed', $t->comment);
        $this->assertSame([$g2->id], $t->groups()->pluck('groups.id')->all());
        $this->assertDatabaseHas('training_participation', ['training_id' => $t->id, 'user_id' => $keep->id, 'attend' => 1]);
        $this->assertDatabaseHas('training_participation', ['training_id' => $t->id, 'user_id' => $drop->id, 'attend' => 0]);
    }

    public function test_update_unknown_training_is_404(): void
    {
        $trainer = $this->makeUser('trainer');

        $this->actingAsJwt($trainer)->putJson('/api/v1/training/999999', $this->payload())->assertNotFound();
    }

    public function test_destroy_hard_deletes_and_cascades(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = TrainingFactory::new()->forGroups([GroupFactory::new()->create()])->create();

        $this->actingAsJwt($trainer)->deleteJson("/api/v1/training/{$t->id}")
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $this->assertDatabaseMissing('trainings', ['id' => $t->id]);
        $this->assertDatabaseCount('training_group', 0);
    }

    public function test_prepared_flag(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = TrainingFactory::new()->create();

        $this->actingAsJwt($trainer)->postJson("/api/v1/training/{$t->id}/prepared")->assertOk()->assertExactJson(['status' => 'ok']);

        $this->assertSame(1, (int) $t->fresh()->prepared);
    }

    public function test_delete_in_future_only_flags_the_training_as_deleted(): void
    {
        $trainer = $this->makeUser('trainer');
        $t = TrainingFactory::new()->create();

        $this->actingAsJwt($trainer)->patchJson("/api/v1/training/{$t->id}/deleteinfuture")->assertOk()->assertExactJson(['status' => 'ok']);

        $this->assertSame(1, (int) $t->fresh()->deleted);
        $this->getJson('/api/v1/training/upcoming')->assertJsonCount(0, 'data');
    }

    public function test_participation_count_counts_attended_evaluated_trainings_per_active_user(): void
    {
        $trainer = $this->makeUser('trainer');
        $g = GroupFactory::new()->create();
        $busy = UserFactory::new()->inGroups([$g])->create(['firstName' => 'Busy', 'familyName' => 'Bee']);
        $lazy = UserFactory::new()->inGroups([$g])->create(['firstName' => 'Lazy', 'familyName' => 'Lou']);
        $inactive = UserFactory::new()->inactive()->inGroups([$g])->create();

        foreach (['2099-01-05', '2099-01-12'] as $day) {
            $t = TrainingFactory::new()->between("$day 18:00:00", "$day 19:00:00")->forGroups([$g])->create(['evaluated' => 1]);
            $t->participants()->attach($busy->id, ['attend' => 1]);
            $t->participants()->attach($inactive->id, ['attend' => 1]);
        }
        $open = TrainingFactory::new()->between('2099-01-19 18:00:00', '2099-01-19 19:00:00')->forGroups([$g])->create(['evaluated' => 0]);
        $open->participants()->attach($lazy->id, ['attend' => 1]);

        $response = $this->actingAsJwt($trainer)->getJson("/api/v1/training/participationcount?groupIds={$g->id}&year=2099")->assertOk();

        $response->assertExactJson([
            ['id' => $busy->id, 'firstName' => 'Busy', 'familyName' => 'Bee', 'total' => 2],
        ]);
    }
}
