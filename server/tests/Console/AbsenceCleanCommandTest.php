<?php

namespace Tests\Console;

use Database\Factories\UserFactory;
use Tests\TestCase;

class AbsenceCleanCommandTest extends TestCase
{
    public function test_removes_absences_that_have_ended_and_keeps_current_ones(): void
    {
        $ended = UserFactory::new()->create(['absenceStart' => '2020-01-01', 'absenceEnd' => '2020-01-10', 'absenceReason' => 'alt']);
        $endsToday = UserFactory::new()->create(['absenceStart' => now()->subDays(3)->format('Y-m-d'), 'absenceEnd' => now()->format('Y-m-d'), 'absenceReason' => 'heute']);
        $running = UserFactory::new()->create(['absenceStart' => '2020-01-01', 'absenceEnd' => now()->addDays(5)->format('Y-m-d'), 'absenceReason' => 'läuft']);
        $none = UserFactory::new()->create();

        $this->artisan('user:absence-clean')->assertSuccessful();

        foreach ([$ended, $endsToday] as $user) {
            $user->refresh();
            $this->assertNull($user->absenceStart);
            $this->assertNull($user->absenceEnd);
            $this->assertNull($user->absenceReason);
        }
        $this->assertSame('läuft', $running->fresh()->absenceReason);
        $this->assertNull($none->fresh()->absenceReason);
    }

    public function test_reports_how_many_users_were_found(): void
    {
        UserFactory::new()->create(['absenceStart' => '2020-01-01', 'absenceEnd' => '2020-01-10']);

        $this->artisan('user:absence-clean')->expectsOutputToContain('Found  1 users with absence in the past')->assertSuccessful();
    }
}
