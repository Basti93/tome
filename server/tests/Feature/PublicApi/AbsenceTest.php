<?php

namespace Tests\Feature\PublicApi;

use App\User;
use Tests\TestCase;

/**
 * simpleuser/{id}/storeAbsence is public (no JWT) and triggers the `notification:absence`
 * Artisan command, which pushes to the trainers of the user's branches via FCM.
 */
class AbsenceTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'absenceStart' => '2030-08-01T00:00:00+0200',
            'absenceEnd' => '2030-08-14T00:00:00+0200',
            'absenceReason' => 'Urlaub',
        ], $overrides);
    }

    public function test_store_absence_without_any_trainer_token_sends_no_push(): void
    {
        $member = $this->makeUser('member');

        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", $this->payload())->assertOk();

        $this->assertSame('Urlaub', $member->fresh()->absenceReason);
    }

    public function test_store_absence_does_not_overwrite_an_existing_absence(): void
    {
        $member = $this->makeUser('member');
        $member->forceFill(['absenceStart' => '2030-01-01', 'absenceEnd' => '2030-01-05', 'absenceReason' => 'alt'])->save();

        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", $this->payload())
            ->assertOk()
            ->assertExactJson([
                'status' => 'absence_exists',
                'message' => 'An absence is already stored for the user',
            ]);

        $this->assertSame('alt', $member->fresh()->absenceReason);
    }

    public function test_store_absence_validates_input(): void
    {
        $member = $this->makeUser('member');

        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['absenceStart', 'absenceEnd']);
    }

    public function test_absence_reason_is_optional_but_limited_to_255_characters(): void
    {
        $member = $this->makeUser('member');
        $other = $this->makeUser('member');

        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", $this->payload(['absenceReason' => null]))->assertOk()->assertJsonPath('status', 'ok');
        $this->assertNull($member->fresh()->absenceReason);

        $this->postJson("/api/v1/simpleuser/{$other->id}/storeAbsence", $this->payload(['absenceReason' => str_repeat('x', 256)]))
            ->assertStatus(422)->assertJsonValidationErrors(['absenceReason']);
    }

    public function test_absence_must_not_start_in_the_past(): void
    {
        $member = $this->makeUser('member');

        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", $this->payload([
            'absenceStart' => now()->subDay()->format(DATE_ATOM),
            'absenceEnd' => now()->addDays(3)->format(DATE_ATOM),
        ]))->assertStatus(422)->assertJsonValidationErrors(['absenceStart']);

        // today is fine
        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", $this->payload([
            'absenceStart' => now()->format(DATE_ATOM),
            'absenceEnd' => now()->addDay()->format(DATE_ATOM),
        ]))->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_absence_end_must_not_be_before_start(): void
    {
        $member = $this->makeUser('member');

        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", $this->payload([
            'absenceStart' => '2030-08-10T00:00:00+0200',
            'absenceEnd' => '2030-08-09T00:00:00+0200',
        ]))->assertStatus(422)->assertJsonValidationErrors(['absenceEnd']);
    }

    public function test_absence_is_limited_to_365_days(): void
    {
        $member = $this->makeUser('member');
        $start = now()->addDays(2);

        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", $this->payload([
            'absenceStart' => $start->format(DATE_ATOM),
            'absenceEnd' => $start->copy()->addDays(366)->format(DATE_ATOM),
        ]))->assertStatus(422)->assertJsonValidationErrors(['absenceEnd']);

        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", $this->payload([
            'absenceStart' => $start->format(DATE_ATOM),
            'absenceEnd' => $start->copy()->addDays(365)->format(DATE_ATOM),
        ]))->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_absence_dates_must_be_iso_8601(): void
    {
        $member = $this->makeUser('member');

        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", $this->payload(['absenceStart' => 'tomorrow']))
            ->assertStatus(422)->assertJsonValidationErrors(['absenceStart']);
    }

    public function test_store_absence_is_throttled_to_ten_requests_per_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/simpleuser/999999/storeAbsence', $this->payload())->assertNotFound();
        }

        $this->postJson('/api/v1/simpleuser/999999/storeAbsence', $this->payload())->assertStatus(429);
    }

    public function test_store_absence_for_unknown_user_is_404(): void
    {
        $this->postJson('/api/v1/simpleuser/999999/storeAbsence', $this->payload())->assertNotFound();
    }

    public function test_the_public_remove_absence_route_was_removed(): void
    {
        $member = $this->makeUser('member');

        $this->postJson("/api/v1/simpleuser/{$member->id}/removeAbsence")->assertStatus(404);
    }
}
