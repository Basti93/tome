<?php

namespace Tests\Console;

use App\Training;
use Database\Factories\ContentFactory;
use Database\Factories\GroupFactory;
use Database\Factories\LocationFactory;
use Database\Factories\TrainingSeriesFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

/**
 * `training:series` uses plain \DateTime / date() (not Carbon), so Carbon::setTestNow cannot freeze it.
 * The tests therefore pick weekdays relative to the real "today" to stay deterministic on every weekday.
 */
class TrainingSeriesCommandTest extends TestCase
{
    /** ISO weekday number (1 = Monday) of today + $days. */
    private function weekday(int $days): int
    {
        return (int) now()->addDays($days)->format('N');
    }

    public function test_creates_the_training_on_the_matching_weekday_within_the_next_seven_days(): void
    {
        $group = GroupFactory::new()->create();
        $trainer = UserFactory::new()->role('trainer')->create();
        $content = ContentFactory::new()->create();
        $location = LocationFactory::new()->create();
        $series = TrainingSeriesFactory::new()
            ->onWeekdays([$this->weekday(3)])->forGroups([$group])->withTrainers([$trainer])
            ->create(['comment' => 'Serie', 'location_id' => $location->id, 'automatic_attend' => 1, 'startTime' => '18:00:00', 'endTime' => '19:30:00']);
        $series->contents()->attach($content->id);

        $this->artisan('training:series')->assertSuccessful();

        $training = Training::firstOrFail();
        $this->assertSame(1, Training::count());
        $this->assertSame(now()->addDays(3)->format('Y-m-d').' 18:00:00', $training->start);
        $this->assertSame(now()->addDays(3)->format('Y-m-d').' 19:30:00', $training->end);
        $this->assertSame('Serie', $training->comment);
        $this->assertSame(1, (int) $training->automatic_attend);
        $this->assertSame($series->id, (int) $training->training_series_id);
        $this->assertSame($location->id, (int) $training->location_id);
        $this->assertSame([$group->id], $training->groups()->pluck('groups.id')->all());
        $this->assertSame([$trainer->id], $training->trainers()->pluck('users.id')->all());
        $this->assertSame([$content->id], $training->contents()->pluck('contents.id')->all());
    }

    public function test_running_it_again_does_not_create_duplicates(): void
    {
        TrainingSeriesFactory::new()->onWeekdays([$this->weekday(2)])->create();

        $this->artisan('training:series')->assertSuccessful();
        $this->artisan('training:series')->assertSuccessful();
        $this->artisan('training:series')->assertSuccessful();

        $this->assertSame(1, Training::count());
    }

    public function test_only_the_selected_weekday_of_the_window_gets_a_training(): void
    {
        TrainingSeriesFactory::new()->onWeekdays([$this->weekday(1)])->create();

        $this->artisan('training:series')->assertSuccessful();

        $this->assertSame(
            [now()->addDay()->format('Y-m-d')],
            Training::all()->map(fn ($t) => substr($t->start, 0, 10))->all()
        );
    }

    public function test_deferred_series_are_skipped_until_the_defer_date_has_passed(): void
    {
        $day = $this->weekday(2);
        TrainingSeriesFactory::new()->onWeekdays([$day])->create(['defer_until' => now()->addDays(1)->format('Y-m-d 00:00:00')]);
        TrainingSeriesFactory::new()->onWeekdays([$day])->create(['defer_until' => now()->format('Y-m-d 00:00:00')]);

        $this->artisan('training:series')->assertSuccessful();
        $this->assertSame(0, Training::count(), 'defer_until >= today (midnight) is still deferred');

        $past = TrainingSeriesFactory::new()->onWeekdays([$day])->create(['defer_until' => now()->subDay()->format('Y-m-d 00:00:00')]);
        $this->artisan('training:series')->assertSuccessful();

        $this->assertSame([$past->id], Training::pluck('training_series_id')->map(fn ($i) => (int) $i)->all());
    }

    public function test_without_any_series_nothing_happens(): void
    {
        $this->artisan('training:series')->assertSuccessful();

        $this->assertSame(0, Training::count());
    }

    public function test_known_quirk_multi_weekday_series_only_create_the_first_matching_day(): void
    {
        // BUG (pinned): the "already created" check uses DAYOFWEEK() (Sunday = 1) against the ISO
        // weekday (Monday = 1) with "<>", so once one training of a series exists in the window, the
        // following matching days are treated as "already created". A series for tomorrow and day +3
        // therefore only produces the first training per run.
        TrainingSeriesFactory::new()->onWeekdays([$this->weekday(1), $this->weekday(3)])->create();

        $this->artisan('training:series')->assertSuccessful();

        $this->assertSame(1, Training::count());
    }
}
