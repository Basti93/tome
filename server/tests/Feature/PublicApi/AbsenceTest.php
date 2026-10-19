<?php

namespace Tests\Feature\PublicApi;

use App\NotificationToken;
use App\User;
use Database\Factories\BranchFactory;
use Database\Factories\GroupFactory;
use Database\Factories\UserFactory;
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

    private function giveToken(User $user, string $token): void
    {
        $t = new NotificationToken;
        $t->user_id = $user->id;
        $t->token = $token;
        $t->save();
    }

    public function test_store_absence_saves_the_absence_and_notifies_the_trainers_of_the_branch(): void
    {
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['branch_id' => $branch->id]);
        $otherBranch = BranchFactory::new()->create();

        $member = UserFactory::new()->inGroups([$group])->create(['firstName' => 'Max', 'familyName' => 'Muster']);
        $trainer = UserFactory::new()->role('trainer')->trainerOfBranches([$branch])->create();
        $foreignTrainer = UserFactory::new()->role('trainer')->trainerOfBranches([$otherBranch])->create();
        $this->giveToken($trainer, 'token-trainer');
        $this->giveToken($foreignTrainer, 'token-foreign');

        $this->fcm->shouldReceive('sendTo')->once()->withArgs(function ($tokens, $n, $pn, $data) {
            $payload = $data->toArray();

            return $tokens === ['token-trainer']
                && $payload['title'] === 'Abwesenheit Max Muster'
                && str_contains($payload['body'], 'Von 01.08.30 bis 14.08.30')
                && str_contains($payload['body'], 'Grund: Urlaub')
                && str_ends_with($payload['click_action'], '/#/absenceForm');
        })->andReturn($this->fcmResponse);

        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", $this->payload())
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);

        $member->refresh();
        $this->assertSame('2030-08-01', $member->absenceStart);
        $this->assertSame('2030-08-14', $member->absenceEnd);
        $this->assertSame('Urlaub', $member->absenceReason);
    }

    public function test_store_absence_without_any_trainer_token_sends_no_push(): void
    {
        $member = $this->makeUser('member');
        $this->fcm->shouldReceive('sendTo')->never();

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
