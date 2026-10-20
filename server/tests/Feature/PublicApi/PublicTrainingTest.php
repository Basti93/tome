<?php

namespace Tests\Feature\PublicApi;

use App\Training;
use Database\Factories\BranchFactory;
use Database\Factories\ContentFactory;
use Database\Factories\GroupFactory;
use Database\Factories\LocationFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\TrainingSeriesFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class PublicTrainingTest extends TestCase
{
    public function test_upcoming_trainings_expose_the_training_resource_shape(): void
    {
        $group = GroupFactory::new()->create();
        $trainer = $this->makeUser('trainer');
        $location = LocationFactory::new()->create();
        $content = ContentFactory::new()->create();
        $participant = UserFactory::new()->create();

        $training = TrainingFactory::new()
            ->between('2099-03-05 18:00:00', '2099-03-05 19:30:00')
            ->forGroups([$group])->withTrainers([$trainer])->withContents([$content])
            ->create(['location_id' => $location->id, 'comment' => 'Bring water', 'automatic_attend' => 1]);
        $training->participants()->attach($participant->id, ['attend' => 1]);

        $response = $this->getJson('/api/v1/training/upcoming')->assertOk();

        $response->assertJsonCount(1, 'data');
        $item = $response->json('data.0');

        $this->assertSame(
            ['id', 'start', 'end', 'locationId', 'groupIds', 'trainerIds', 'participants', 'contentIds', 'comment', 'prepared', 'evaluated', 'automaticAttend'],
            array_keys($item)
        );
        $this->assertSame($training->id, $item['id']);
        $this->assertSame('2099-03-05T18:00:00+01:00', $item['start']);
        $this->assertSame('2099-03-05T19:30:00+01:00', $item['end']);
        $this->assertSame($location->id, $item['locationId']);
        $this->assertSame([$group->id], $item['groupIds']);
        $this->assertSame([$trainer->id], $item['trainerIds']);
        $this->assertSame([$content->id], $item['contentIds']);
        $this->assertSame('Bring water', $item['comment']);
        $this->assertSame(0, $item['prepared']);
        $this->assertSame(0, $item['evaluated']);
        $this->assertSame(1, $item['automaticAttend']);

        // without login only ids and the attendance flag are exposed, never the cancel reason
        $this->assertCount(1, $item['participants']);
        $this->assertSame(
            ['attend' => 1, 'trainingId' => $training->id, 'userId' => $participant->id],
            $item['participants'][0]
        );
    }

    public function test_logged_in_users_still_get_the_full_participation_rows(): void
    {
        $participant = UserFactory::new()->create();
        $training = TrainingFactory::new()->between('2099-03-05 18:00:00', '2099-03-05 19:30:00')->create();
        $training->participants()->attach($participant->id, ['attend' => 0, 'cancelreason' => 'krank']);

        $item = $this->actingAsJwt($this->makeUser('member'))->getJson('/api/v1/training/upcoming')->assertOk()->json('data.0');

        $this->assertSame(
            ['attend' => 0, 'cancelreason' => 'krank', 'trainingId' => $training->id, 'userId' => $participant->id],
            $item['participants'][0]
        );
    }

    public function test_upcoming_excludes_past_and_deleted_trainings_and_is_sorted_ascending(): void
    {
        $later = TrainingFactory::new()->between('2099-04-02 18:00:00', '2099-04-02 19:00:00')->create();
        $sooner = TrainingFactory::new()->between('2099-04-01 18:00:00', '2099-04-01 19:00:00')->create();
        TrainingFactory::new()->inPast()->create();
        TrainingFactory::new()->between('2099-04-03 18:00:00', '2099-04-03 19:00:00')->create(['deleted_at' => now()]);

        $ids = collect($this->getJson('/api/v1/training/upcoming')->assertOk()->json('data'))->pluck('id')->all();

        $this->assertSame([$sooner->id, $later->id], $ids);
    }

    public function test_upcoming_is_limited_to_ten(): void
    {
        TrainingFactory::new()->count(12)->create();

        $this->getJson('/api/v1/training/upcoming')->assertOk()->assertJsonCount(10, 'data');
    }

    public function test_upcoming_can_be_filtered_by_group_and_branch(): void
    {
        $branchA = BranchFactory::new()->create();
        $branchB = BranchFactory::new()->create();
        $groupA = GroupFactory::new()->create(['branch_id' => $branchA->id]);
        $groupB = GroupFactory::new()->create(['branch_id' => $branchB->id]);
        $a = TrainingFactory::new()->forGroups([$groupA])->create();
        $b = TrainingFactory::new()->forGroups([$groupB])->create();

        $byGroup = collect($this->getJson("/api/v1/training/upcoming?groupIds={$groupA->id}")->json('data'))->pluck('id')->all();
        $byBranch = collect($this->getJson("/api/v1/training/upcoming?branchId={$branchB->id}")->json('data'))->pluck('id')->all();

        $this->assertSame([$a->id], $byGroup);
        $this->assertSame([$b->id], $byBranch);
    }

    public function test_single_upcoming_training(): void
    {
        $training = TrainingFactory::new()->between('2099-05-01 10:00:00', '2099-05-01 11:00:00')->create();

        $this->getJson("/api/v1/training/upcoming/{$training->id}")->assertOk()
            ->assertJsonPath('data.id', $training->id)
            ->assertJsonPath('data.start', '2099-05-01T10:00:00+02:00');
    }

    public function test_single_upcoming_training_in_the_past_returns_a_200_message(): void
    {
        $training = TrainingFactory::new()->inPast()->create();

        $this->getJson("/api/v1/training/upcoming/{$training->id}")->assertOk()->assertExactJson([
            'status' => 'ok',
            'message' => "Training with id {$training->id} could not be found or is in the past",
        ]);
    }

    public function test_single_upcoming_training_unknown_id_is_404(): void
    {
        $this->getJson('/api/v1/training/upcoming/999999')->assertNotFound();
    }

    public function test_simple_calendar_exposes_the_reduced_resource(): void
    {
        $group = GroupFactory::new()->create();
        $trainer = UserFactory::new()->create();
        $training = TrainingFactory::new()
            ->between('2099-06-10 18:00:00', '2099-06-10 19:00:00')
            ->forGroups([$group])->withTrainers([$trainer])
            ->create(['comment' => 'c']);
        TrainingFactory::new()->between('2099-07-10 18:00:00', '2099-07-10 19:00:00')->create();

        $response = $this->getJson('/api/v1/training/simplecalendar?start=2099-06-01&end=2099-06-30')->assertOk();

        $response->assertJsonCount(1, 'data');
        $this->assertSame(
            ['id', 'start', 'end', 'locationId', 'groupIds', 'trainerIds', 'contentIds', 'comment'],
            array_keys($response->json('data.0'))
        );
        $response->assertJsonPath('data.0.id', $training->id)
            ->assertJsonPath('data.0.groupIds', [$group->id])
            ->assertJsonPath('data.0.trainerIds', [$trainer->id])
            ->assertJsonPath('data.0.comment', 'c');
    }

    public function test_simple_calendar_with_equal_start_and_end_returns_that_day(): void
    {
        $day = TrainingFactory::new()->between('2099-06-10 18:00:00', '2099-06-10 19:00:00')->create();
        TrainingFactory::new()->between('2099-06-11 18:00:00', '2099-06-11 19:00:00')->create();

        $ids = collect($this->getJson('/api/v1/training/simplecalendar?start=2099-06-10&end=2099-06-10')->json('data'))->pluck('id')->all();

        $this->assertSame([$day->id], $ids);
    }

    public function test_simple_calendar_can_be_filtered_by_group(): void
    {
        $g1 = GroupFactory::new()->create();
        $g2 = GroupFactory::new()->create();
        $t1 = TrainingFactory::new()->between('2099-06-10 18:00:00', '2099-06-10 19:00:00')->forGroups([$g1])->create();
        TrainingFactory::new()->between('2099-06-11 18:00:00', '2099-06-11 19:00:00')->forGroups([$g2])->create();

        $ids = collect($this->getJson("/api/v1/training/simplecalendar?start=2099-06-01&end=2099-06-30&groupIds={$g1->id}")->json('data'))->pluck('id')->all();

        $this->assertSame([$t1->id], $ids);
    }

    public function test_planned_trainings_are_generated_from_deferred_series_beyond_next_week(): void
    {
        // Characterization: only series with a defer_until date BEFORE the requested start are
        // considered ("defer_until < start" is never true for NULL), and the generated entries
        // get negative temporary ids (-seriesId * 1000, then decreasing).
        $group = GroupFactory::new()->create();
        $series = TrainingSeriesFactory::new()->onWeekdays([1, 2, 3, 4, 5, 6, 7])->forGroups([$group])
            ->create(['defer_until' => '2099-01-01 00:00:00', 'comment' => 'planned']);
        $deferred = TrainingSeriesFactory::new()->forGroups([$group])->create(['defer_until' => null]);

        $response = $this->getJson('/api/v1/training/simplecalendar/planned?start=2099-02-01&end=2099-02-04')->assertOk();

        $data = $response->json('data');
        $this->assertCount(3, $data);
        $this->assertSame(-$series->id * 1000, $data[0]['id']);
        $this->assertSame(-$series->id * 1000 - 1, $data[1]['id']);
        $this->assertSame($series->id, $data[0]['trainingSeriesId']);
        $this->assertSame('planned', $data[0]['comment']);
        $this->assertSame([$group->id], $data[0]['groupIds']);
        $this->assertStringStartsWith('2099-02-01T18:00:00', $data[0]['start']);
        $this->assertStringStartsWith('2099-02-01T19:30:00', $data[0]['end']);
        $this->assertNotContains($deferred->id, array_column($data, 'trainingSeriesId'));
    }

    public function test_planned_trainings_are_empty_within_the_next_seven_days(): void
    {
        $series = TrainingSeriesFactory::new()->create(['defer_until' => '2000-01-01 00:00:00']);

        $start = now()->format('Y-m-d');
        $end = now()->addDays(2)->format('Y-m-d');

        $this->getJson("/api/v1/training/simplecalendar/planned?start=$start&end=$end")
            ->assertOk()->assertExactJson(['data' => []]);
        $this->assertNotNull($series->id);
    }

    public function test_public_listing_endpoints_need_no_authentication(): void
    {
        foreach (['simpleuser', 'simpleuser/trainers', 'group', 'branch', 'location', 'content', 'trainingSeries', 'training/upcoming'] as $path) {
            $this->getJson("/api/v1/$path")->assertOk();
        }
        $this->assertSame(0, Training::count());
    }
}
