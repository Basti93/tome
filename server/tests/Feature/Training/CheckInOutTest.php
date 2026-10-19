<?php

namespace Tests\Feature\Training;

use App\NotificationToken;
use Database\Factories\GroupFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

/**
 * Authenticated check-in / check-out (permission checkin-training).
 */
class CheckInOutTest extends TestCase
{
    public function test_a_member_can_check_in_a_user_of_the_training_group(): void
    {
        $group = GroupFactory::new()->create();
        $member = UserFactory::new()->role('member')->inGroups([$group])->create();
        $training = TrainingFactory::new()->forGroups([$group])->create();

        $this->actingAsJwt($member)->postJson("/api/v1/training/{$training->id}/checkin/{$member->id}")
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $this->assertDatabaseHas('training_participation', ['training_id' => $training->id, 'user_id' => $member->id, 'attend' => 1]);
    }

    public function test_check_in_is_forbidden_outside_the_training_group(): void
    {
        $member = UserFactory::new()->role('member')->inGroups([GroupFactory::new()->create()])->create();
        $training = TrainingFactory::new()->forGroups([GroupFactory::new()->create()])->create();

        $this->actingAsJwt($member)->postJson("/api/v1/training/{$training->id}/checkin/{$member->id}")
            ->assertStatus(403)->assertExactJson(['status' => 'User is not assigned to the training group']);
    }

    public function test_check_in_unknown_user_or_training_is_404(): void
    {
        $member = $this->makeUser('member');
        $training = TrainingFactory::new()->create();

        $this->actingAsJwt($member)->postJson("/api/v1/training/{$training->id}/checkin/999999")->assertNotFound();
        $this->actingAsJwt($member)->postJson("/api/v1/training/999999/checkin/{$member->id}")->assertNotFound();
    }

    public function test_check_in_works_for_inactive_users_when_authenticated(): void
    {
        // unlike the unregistered variant, the authenticated endpoint does not filter on "active"
        $group = GroupFactory::new()->create();
        $trainer = $this->makeUser('trainer');
        $inactive = UserFactory::new()->inactive()->inGroups([$group])->create();
        $training = TrainingFactory::new()->forGroups([$group])->create();

        $this->actingAsJwt($trainer)->postJson("/api/v1/training/{$training->id}/checkin/{$inactive->id}")->assertStatus(201);
    }

    public function test_check_out_far_in_advance_marks_not_attending(): void
    {
        $group = GroupFactory::new()->create();
        $member = UserFactory::new()->role('member')->inGroups([$group])->create();
        $training = TrainingFactory::new()->forGroups([$group])->create();
        $training->participants()->attach($member->id, ['attend' => 1]);

        $this->actingAsJwt($member)->postJson("/api/v1/training/{$training->id}/checkout/{$member->id}")
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $this->assertDatabaseHas('training_participation', ['training_id' => $training->id, 'user_id' => $member->id, 'attend' => 0]);
    }

    public function test_check_out_within_24_hours_needs_a_reason_then_notifies_trainer(): void
    {
        $group = GroupFactory::new()->create();
        $trainer = $this->makeUser('trainer');
        $token = new NotificationToken;
        $token->user_id = $trainer->id;
        $token->token = 'trainer-token';
        $token->save();
        $member = UserFactory::new()->role('member')->inGroups([$group])->create();
        $training = TrainingFactory::new()
            ->between(now()->addHours(3)->format('Y-m-d H:i:s'), now()->addHours(4)->format('Y-m-d H:i:s'))
            ->forGroups([$group])->withTrainers([$trainer])->create();

        $this->actingAsJwt($member)->postJson("/api/v1/training/{$training->id}/checkout/{$member->id}")
            ->assertStatus(201)->assertJsonPath('status', 'cancel_needs_reason');

        $this->fcm->shouldReceive('sendTo')->once()->withArgs(fn ($tokens) => $tokens === ['trainer-token'])->andReturn($this->fcmResponse);

        $this->actingAsJwt($member)->postJson("/api/v1/training/{$training->id}/checkout/{$member->id}", ['reason' => 'Termin'])
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $this->assertDatabaseHas('training_participation', [
            'training_id' => $training->id, 'user_id' => $member->id, 'attend' => 0, 'cancelreason' => 'Termin',
        ]);
    }

    public function test_check_out_is_forbidden_outside_the_group(): void
    {
        $member = UserFactory::new()->role('member')->create();
        $training = TrainingFactory::new()->forGroups([GroupFactory::new()->create()])->create();

        $this->actingAsJwt($member)->postJson("/api/v1/training/{$training->id}/checkout/{$member->id}")
            ->assertStatus(403)->assertJsonPath('status', 'not_assigned');
    }
}
