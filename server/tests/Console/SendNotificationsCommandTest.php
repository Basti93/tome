<?php

namespace Tests\Console;

use App\NotificationToken;
use App\UserTrainingNotification;
use Database\Factories\TrainingFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class SendNotificationsCommandTest extends TestCase
{
    private function token(int $userId, string $token): void
    {
        $t = new NotificationToken;
        $t->user_id = $userId;
        $t->token = $token;
        $t->save();
    }

    public function test_sends_one_push_to_all_tokens_of_the_given_users(): void
    {
        $a = UserFactory::new()->create();
        $b = UserFactory::new()->create();
        $this->token($a->id, 'a1');
        $this->token($a->id, 'a2');
        $this->token($b->id, 'b1');

        $this->fcm->shouldReceive('sendTo')->once()->withArgs(function ($tokens, $n, $pn, $data) {
            $payload = $data->toArray();
            sort($tokens);

            return $tokens === ['a1', 'a2', 'b1'] && $payload['title'] === 'Hallo' && $payload['body'] === 'Welt';
        })->andReturn($this->fcmResponse);

        $this->artisan('notification:sendToUsers', ['userIds' => "{$a->id},{$b->id}", 'title' => 'Hallo', 'data' => 'Welt'])
            ->assertSuccessful();
    }

    public function test_does_not_call_fcm_without_tokens(): void
    {
        $a = UserFactory::new()->create();
        $this->fcm->shouldReceive('sendTo')->never();

        $this->artisan('notification:sendToUsers', ['userIds' => (string) $a->id, 'title' => 'x', 'data' => 'y'])
            ->expectsOutputToContain('0 Recipients found')->assertSuccessful();
    }

    public function test_tokens_reported_as_invalid_by_fcm_are_deleted(): void
    {
        $a = UserFactory::new()->create();
        $this->token($a->id, 'good');
        $this->token($a->id, 'stale');
        $this->fcmResponse->shouldReceive('tokensToDelete')->andReturn(['stale']);

        $this->artisan('notification:sendToUsers', ['userIds' => (string) $a->id, 'title' => 'x', 'data' => 'y'])->assertSuccessful();

        $this->assertDatabaseHas('user_notification_token', ['token' => 'good']);
        $this->assertDatabaseMissing('user_notification_token', ['token' => 'stale']);
    }

    public function test_training_context_notifications_are_sent_once_when_status_is_stored(): void
    {
        $user = UserFactory::new()->create();
        $this->token($user->id, 'tok');
        $training = TrainingFactory::new()->create();
        $args = ['userIds' => (string) $user->id, 'trainingId' => $training->id, 'title' => 't', 'data' => 'd', 'notificationType' => 1, '--storeStatus' => true];

        $this->fcm->shouldReceive('sendTo')->once()->andReturn($this->fcmResponse);

        $this->artisan('notification:sendToUsersTrainingContext', $args)->assertSuccessful();
        $this->artisan('notification:sendToUsersTrainingContext', $args)->assertSuccessful();

        $this->assertSame(1, UserTrainingNotification::where('user_id', $user->id)->where('training_id', $training->id)->count());
    }
}
