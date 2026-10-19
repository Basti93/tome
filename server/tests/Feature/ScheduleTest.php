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

        $this->assertCount(7, app(Schedule::class)->events());
        $this->assertEqualsCanonicalizing([
            'training:automatic-attend',
            'notification:upcomingTrainingForTrainer',
            'notification:upcomingTraining',
            'training:series',
            'user:absence-clean',
            'emails:last-month-statistics',
            'tome:retention',
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
        $this->assertSame('0 0 1 * *', $events['emails:last-month-statistics']->expression);
        $this->assertSame('0 3 1 * *', $events['tome:retention']->expression, 'retention runs monthly');
    }

    public function test_every_scheduled_command_exists(): void
    {
        $registered = array_keys(\Artisan::all());

        foreach (array_keys($this->events()) as $command) {
            $this->assertContains($command, $registered);
        }
    }
}
