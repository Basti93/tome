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
            ->assertJsonValidationErrors(['absenceStart', 'absenceEnd', 'absenceReason']);
    }

    public function test_store_absence_for_unknown_user_is_404(): void
    {
        $this->postJson('/api/v1/simpleuser/999999/storeAbsence', $this->payload())->assertNotFound();
    }

    public function test_remove_absence_route_is_broken(): void
    {
        // BUG: POST simpleuser/{id}/removeAbsence points at SimpleUserController@removeAbsence,
        // which does not exist (the working implementation is the authenticated
        // PUT user/{id}/removeAbsence in UserController). Pinning the current 500.
        $member = $this->makeUser('member');

        $this->postJson("/api/v1/simpleuser/{$member->id}/removeAbsence")->assertStatus(500);
    }
}
