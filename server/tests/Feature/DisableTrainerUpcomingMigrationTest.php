<?php

namespace Tests\Feature;

use Tests\TestCase;

class DisableTrainerUpcomingMigrationTest extends TestCase
{
    public function test_it_switches_the_trainer_reminder_off_for_everybody_with_stored_preferences_and_keeps_the_rest(): void
    {
        $stored = $this->makeUser('trainer', ['notification_preferences' => ['trainer_upcoming' => true, 'absence_alert' => false, 'admin_messages' => true]]);
        $partial = $this->makeUser('trainer', ['notification_preferences' => ['absence_alert' => true]]);
        $none = $this->makeUser('trainer');

        $migration = require database_path('migrations/2026_10_20_100000_disable_trainer_upcoming_reminder.php');
        $migration->up();

        $this->assertEqualsCanonicalizing(['trainer_upcoming' => false, 'absence_alert' => false, 'admin_messages' => true], $stored->fresh()->notification_preferences);
        $this->assertFalse($partial->fresh()->notification_preferences['trainer_upcoming']);
        $this->assertTrue($partial->fresh()->notification_preferences['absence_alert']);
        $this->assertNull($none->fresh()->notification_preferences, 'users without stored preferences get the code default (off)');
        $this->assertFalse($none->fresh()->isNotificationEnabled('trainer_upcoming'));

        $migration->down(); // no-op
        $this->assertFalse($stored->fresh()->notification_preferences['trainer_upcoming']);
    }
}
