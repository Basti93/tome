<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * The schedule lives in routes/console.php (Laravel 11+ style; the old app/Console/Kernel.php was
 * never loaded any more, so nothing ran at all).
 */
class ScheduleTest extends TestCase
{
    /** @return array<string, Event> artisan command => event */
    private function events(): array
    {
        $events = [];
        foreach (app(Schedule::class)->events() as $event) {
            preg_match("/artisan'? '?([a-zA-Z:\\-]+)/", $event->command, $match);
            $events[$match[1]] = $event;
        }

        return $events;
    }

    public function test_all_jobs_are_registered_exactly_once(): void
    {
        $events = $this->events();

        $this->assertCount(9, app(Schedule::class)->events());
        $this->assertEqualsCanonicalizing([
            'training:automatic-attend',
            'notification:upcomingTrainingForTrainer',
            'notification:upcomingTraining',
            'notification:evaluation-reminder',
            'training:series',
            'user:absence-clean',
            'emails:last-month-statistics',
            'tome:retention',
            'notification:birthdays',
        ], array_keys($events));
    }

    public function test_the_reminders_run_every_30_minutes_between_6_and_22_o_clock(): void
    {
        foreach (['notification:upcomingTraining', 'notification:upcomingTrainingForTrainer', 'training:automatic-attend'] as $command) {
            $event = $this->events()[$command];

            $this->assertSame('*/30 * * * *', $event->expression, $command);
            $filters = (new \ReflectionProperty($event, 'filters'))->getValue($event);
            $this->assertNotEmpty($filters, "$command is restricted to the day time window");
        }
    }

    public function test_the_other_jobs_have_their_expected_cadence(): void
    {
        $events = $this->events();

        $this->assertSame('0 * * * *', $events['training:series']->expression);
        $this->assertSame('0 1 * * *', $events['user:absence-clean']->expression);
        $this->assertSame('0 20 * * *', $events['notification:evaluation-reminder']->expression);
        $this->assertSame('0 0 1 * *', $events['emails:last-month-statistics']->expression);
        $this->assertSame('0 3 1 * *', $events['tome:retention']->expression, 'retention runs monthly');
        $this->assertSame('0 7 * * *', $events['notification:birthdays']->expression, 'birthdays every morning at 7');
    }

    public function test_every_scheduled_command_exists(): void
    {
        $registered = array_keys(\Artisan::all());

        foreach (array_keys($this->events()) as $command) {
            $this->assertContains($command, $registered);
        }
    }

    public function test_the_scheduled_retention_run_is_off_by_default_and_can_be_enabled(): void
    {
        $this->assertFalse(config('tome.retention.enabled'), 'default: off, so nothing is deleted right after go-live');
        $event = $this->events()['tome:retention'];
        $this->assertFalse($event->filtersPass($this->app));

        config(['tome.retention.enabled' => true]);
        $this->assertTrue($event->filtersPass($this->app));
    }

    public function test_a_manual_retention_dry_run_works_while_the_schedule_is_off(): void
    {
        $this->assertFalse(config('tome.retention.enabled'));

        $this->artisan('tome:retention', ['--dry-run' => true])->assertSuccessful();
    }
}
