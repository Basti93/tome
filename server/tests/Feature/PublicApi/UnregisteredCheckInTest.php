<?php

namespace Tests\Feature\PublicApi;

use App\Group;
use App\NotificationToken;
use App\Training;
use App\User;
use Database\Factories\GroupFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

/**
 * Unregistered users (cookie based identification only) may check in/out without any JWT.
 */
class UnregisteredCheckInTest extends TestCase
{
    private Group $group;

    private Training $training;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = GroupFactory::new()->create();
        $this->training = TrainingFactory::new()->forGroups([$this->group])->create();
    }

    private function participation(Training $training, User $user): ?object
    {
        return \DB::table('training_participation')->where('training_id', $training->id)->where('user_id', $user->id)->first();
    }

    public function test_active_user_in_the_training_group_can_check_in_without_authentication(): void
    {
        $user = UserFactory::new()->unregistered()->inGroups([$this->group])->create();

        $this->postJson("/api/v1/training/{$this->training->id}/checkinunregistered/{$user->id}")
            ->assertStatus(201)
            ->assertExactJson(['status' => 'ok']);

        $this->assertSame(1, (int) $this->participation($this->training, $user)->attend);
    }

    public function test_checking_in_twice_keeps_a_single_participation(): void
    {
        $user = UserFactory::new()->unregistered()->inGroups([$this->group])->create();

        $this->postJson("/api/v1/training/{$this->training->id}/checkinunregistered/{$user->id}")->assertStatus(201);
        $this->postJson("/api/v1/training/{$this->training->id}/checkinunregistered/{$user->id}")->assertStatus(201);

        $this->assertDatabaseCount('training_participation', 1);
    }

    public function test_check_in_is_forbidden_for_a_user_outside_the_training_group(): void
    {
        $user = UserFactory::new()->unregistered()->inGroups([GroupFactory::new()->create()])->create();

        $this->postJson("/api/v1/training/{$this->training->id}/checkinunregistered/{$user->id}")
            ->assertStatus(403)
            ->assertExactJson(['status' => 'User is not assigned to the training group']);

        $this->assertNull($this->participation($this->training, $user));
    }

    public function test_check_in_is_forbidden_for_a_user_without_groups(): void
    {
        $user = UserFactory::new()->unregistered()->create();

        $this->postJson("/api/v1/training/{$this->training->id}/checkinunregistered/{$user->id}")->assertStatus(403);
    }

    public function test_check_in_of_an_inactive_user_is_404(): void
    {
        $user = UserFactory::new()->unregistered()->inactive()->inGroups([$this->group])->create();

        $this->postJson("/api/v1/training/{$this->training->id}/checkinunregistered/{$user->id}")->assertNotFound();
        $this->assertDatabaseCount('training_participation', 0);
    }

    public function test_check_in_with_unknown_user_or_training_is_404(): void
    {
        $user = UserFactory::new()->inGroups([$this->group])->create();

        $this->postJson("/api/v1/training/{$this->training->id}/checkinunregistered/999999")->assertNotFound();
        $this->postJson("/api/v1/training/999999/checkinunregistered/{$user->id}")->assertNotFound();
    }

    public function test_check_out_far_in_advance_needs_no_reason_and_marks_not_attending(): void
    {
        $user = UserFactory::new()->unregistered()->inGroups([$this->group])->create();
        $this->training->participants()->attach($user->id, ['attend' => 1]);
        $this->fcm->shouldReceive('sendTo')->never();

        $this->postJson("/api/v1/training/{$this->training->id}/checkoutunregistered/{$user->id}")
            ->assertStatus(201)
            ->assertExactJson(['status' => 'ok']);

        $participation = $this->participation($this->training, $user);
        $this->assertSame(0, (int) $participation->attend);
        $this->assertNull($participation->cancelreason);
    }

    public function test_check_out_without_prior_participation_creates_a_not_attending_row(): void
    {
        $user = UserFactory::new()->unregistered()->inGroups([$this->group])->create();

        $this->postJson("/api/v1/training/{$this->training->id}/checkoutunregistered/{$user->id}")->assertStatus(201);

        $this->assertSame(0, (int) $this->participation($this->training, $user)->attend);
    }

    public function test_check_out_within_24_hours_requires_a_reason(): void
    {
        $user = UserFactory::new()->unregistered()->inGroups([$this->group])->create();
        $soon = TrainingFactory::new()->between(now()->addHours(5)->format('Y-m-d H:i:s'), now()->addHours(6)->format('Y-m-d H:i:s'))
            ->forGroups([$this->group])->create();
        $soon->participants()->attach($user->id, ['attend' => 1]);

        $this->postJson("/api/v1/training/{$soon->id}/checkoutunregistered/{$user->id}")
            ->assertStatus(201)
            ->assertExactJson([
                'status' => 'cancel_needs_reason',
                'message' => 'The training is in the next 24 hours and needs a reason for cancellation',
            ]);

        $this->assertSame(1, (int) $this->participation($soon, $user)->attend, 'nothing changed without a reason');
    }

    public function test_check_out_within_24_hours_with_reason_cancels_and_notifies_the_trainers(): void
    {
        $trainer = $this->makeUser('trainer');
        $token = new NotificationToken;
        $token->user_id = $trainer->id;
        $token->token = 'trainer-device-token';
        $token->save();

        $user = UserFactory::new()->unregistered()->inGroups([$this->group])->create(['firstName' => 'Kim', 'familyName' => 'Kind']);
        $soon = TrainingFactory::new()->between(now()->addHours(5)->format('Y-m-d H:i:s'), now()->addHours(6)->format('Y-m-d H:i:s'))
            ->forGroups([$this->group])->withTrainers([$trainer])->create();
        $soon->participants()->attach($user->id, ['attend' => 1]);

        $this->fcm->shouldReceive('sendTo')->once()->withArgs(function ($tokens, $notification, $payloadNotification, $data) {
            $payload = $data->toArray();

            return $tokens === ['trainer-device-token']
                && $notification === null
                && $payloadNotification === null
                && str_starts_with($payload['title'], 'Absage für das Training um ')
                && str_contains($payload['body'], 'Sportler: Kim Kind')
                && str_contains($payload['body'], 'Grund: krank');
        })->andReturn($this->fcmResponse);

        $this->postJson("/api/v1/training/{$soon->id}/checkoutunregistered/{$user->id}", ['reason' => 'krank'])
            ->assertStatus(201)
            ->assertExactJson(['status' => 'ok']);

        $participation = $this->participation($soon, $user);
        $this->assertSame(0, (int) $participation->attend);
        $this->assertSame('krank', $participation->cancelreason);
    }

    public function test_check_out_is_forbidden_for_a_user_outside_the_training_group(): void
    {
        $user = UserFactory::new()->unregistered()->inGroups([GroupFactory::new()->create()])->create();

        $this->postJson("/api/v1/training/{$this->training->id}/checkoutunregistered/{$user->id}")
            ->assertStatus(403)
            ->assertExactJson([
                'status' => 'not_assigned',
                'message' => 'User is not assigned to the training group',
            ]);
    }

    public function test_check_out_of_an_inactive_user_is_404(): void
    {
        $user = UserFactory::new()->unregistered()->inactive()->inGroups([$this->group])->create();

        $this->postJson("/api/v1/training/{$this->training->id}/checkoutunregistered/{$user->id}")->assertNotFound();
    }
}
