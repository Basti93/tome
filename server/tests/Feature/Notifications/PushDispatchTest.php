<?php

namespace Tests\Feature\Notifications;

use App\NotificationToken;
use App\Services\FirebaseMessagingService;
use App\Training;
use App\User;
use App\UserTrainingNotification;
use Database\Factories\BranchFactory;
use Database\Factories\GroupFactory;
use Database\Factories\LocationFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\UserFactory;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

/**
 * The notification commands do `new FirebaseMessagingService()` themselves, so the class is replaced
 * with a Mockery "overload" mock. That only works if the real class was never autoloaded, hence every
 * test runs in its own PHP process. Nothing is ever sent to Firebase.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class PushDispatchTest extends TestCase
{
    /** @var MockInterface */
    private $firebase;

    /** @var array<int, array{tokens: array, title: string, body: string, data: array}> */
    private array $sent = [];

    private array $result = ['success' => 1, 'failure' => 0, 'tokensToDelete' => [], 'tokensToRetry' => []];

    protected function setUp(): void
    {
        parent::setUp();

        $this->firebase = Mockery::mock('overload:'.FirebaseMessagingService::class);
        $this->firebase->shouldReceive('sendToDevices')->andReturnUsing(function ($tokens, $title, $body, $data = []) {
            $this->sent[] = ['tokens' => (array) $tokens, 'title' => $title, 'body' => $body, 'data' => $data];

            return $this->result;
        })->byDefault();
    }

    private function device(User $user, string $token): User
    {
        $t = new NotificationToken;
        $t->user_id = $user->id;
        $t->token = $token;
        $t->save();

        return $user;
    }

    private function userWithToken(string $token, array $preferences = []): User
    {
        $user = UserFactory::new()->create($preferences ? ['notification_preferences' => $preferences] : []);

        return $this->device($user, $token);
    }

    private function allTokens(): array
    {
        $tokens = array_merge(...array_column($this->sent, 'tokens') ?: [[]]);
        sort($tokens);

        return $tokens;
    }

    // ---- notification:sendToUsers -------------------------------------------------

    public function test_send_to_users_sends_one_message_with_title_body_and_click_action(): void
    {
        $a = $this->userWithToken('a1');
        $this->device($a, 'a2');
        $b = $this->userWithToken('b1');

        $this->artisan('notification:sendToUsers', ['userIds' => "{$a->id},{$b->id}", 'title' => 'Hallo', 'data' => 'Welt'])->assertSuccessful();

        $this->assertCount(1, $this->sent);
        $this->assertSame(['a1', 'a2', 'b1'], $this->allTokens());
        $this->assertSame('Hallo', $this->sent[0]['title']);
        $this->assertSame('Welt', $this->sent[0]['body']);
        $this->assertSame(['click_action' => config('app.vue_url')], $this->sent[0]['data']);
    }

    public function test_send_to_users_skips_users_who_disabled_admin_messages(): void
    {
        $on = $this->userWithToken('on');
        $off = $this->userWithToken('off', ['admin_messages' => false]);

        $this->artisan('notification:sendToUsers', ['userIds' => "{$on->id},{$off->id}", 'title' => 't', 'data' => 'd'])
            ->expectsOutputToContain('has disabled admin_messages notifications')
            ->assertSuccessful();

        $this->assertSame(['on'], $this->allTokens());
    }

    public function test_send_to_users_honours_the_type_option(): void
    {
        $user = $this->userWithToken('tok', ['absence_alert' => false]);
        $args = ['userIds' => (string) $user->id, 'title' => 't', 'data' => 'd'];

        $this->artisan('notification:sendToUsers', $args)->assertSuccessful(); // default type admin_messages: enabled
        $this->assertCount(1, $this->sent);

        $this->artisan('notification:sendToUsers', $args + ['--type' => 'absence_alert'])->assertSuccessful();
        $this->assertCount(1, $this->sent, 'absence_alert is disabled -> nothing more sent');
    }

    public function test_send_to_users_does_not_contact_firebase_when_nobody_is_eligible(): void
    {
        $off = $this->userWithToken('off', ['admin_messages' => false]);
        $noToken = UserFactory::new()->create();
        $this->firebase->shouldReceive('sendToDevices')->never();

        $this->artisan('notification:sendToUsers', ['userIds' => "{$off->id},{$noToken->id}", 'title' => 't', 'data' => 'd'])
            ->expectsOutputToContain('0 Recipients found')->assertSuccessful();
    }

    public function test_tokens_reported_invalid_by_firebase_are_deleted(): void
    {
        $user = $this->userWithToken('good');
        $this->device($user, 'stale');
        $this->result = ['success' => 1, 'failure' => 1, 'tokensToDelete' => ['stale'], 'tokensToRetry' => []];

        $this->artisan('notification:sendToUsers', ['userIds' => (string) $user->id, 'title' => 't', 'data' => 'd'])->assertSuccessful();

        $this->assertDatabaseHas('user_notification_token', ['token' => 'good']);
        $this->assertDatabaseMissing('user_notification_token', ['token' => 'stale']);
    }

    // ---- notification:sendToUsersTrainingContext ---------------------------------

    /** @return array<string, array{0: int, 1: string}> notificationType => preference key */
    public static function trainingTypes(): array
    {
        return [
            'type 1 = trainer_upcoming' => [1, 'trainer_upcoming'],
            'type 2 = trainer_cancel_alert' => [2, 'trainer_cancel_alert'],
            'type 3 = participant_upcoming' => [3, 'participant_upcoming'],
        ];
    }

    #[DataProvider('trainingTypes')]
    public function test_training_context_skips_users_who_disabled_the_matching_preference(int $type, string $preference): void
    {
        $on = $this->userWithToken('on');
        $off = $this->userWithToken('off', [$preference => false]);
        $otherOff = $this->userWithToken('other', collect(['trainer_upcoming', 'trainer_cancel_alert', 'participant_upcoming'])
            ->reject(fn ($p) => $p === $preference)->mapWithKeys(fn ($p) => [$p => false])->all());
        $training = TrainingFactory::new()->create();

        $this->artisan('notification:sendToUsersTrainingContext', [
            'userIds' => "{$on->id},{$off->id},{$otherOff->id}", 'trainingId' => $training->id,
            'title' => 't', 'data' => 'd', 'notificationType' => $type,
        ])->assertSuccessful();

        $this->assertSame(['on', 'other'], $this->allTokens(), 'only the preference matching the type is evaluated');
    }

    public function test_training_context_type_given_as_a_string_still_maps_to_the_preference_and_is_stored_as_int(): void
    {
        $off = $this->userWithToken('off', ['participant_upcoming' => false]);
        $on = $this->userWithToken('on');
        $training = TrainingFactory::new()->create();

        // console arguments always arrive as strings
        $this->artisan('notification:sendToUsersTrainingContext', [
            'userIds' => "{$off->id},{$on->id}", 'trainingId' => (string) $training->id, 'title' => 't', 'data' => 'd',
            'notificationType' => '3', '--storeStatus' => true,
        ])->assertSuccessful();

        $this->assertSame(['on'], $this->allTokens());
        $this->assertSame(3, UserTrainingNotification::where('user_id', $on->id)->value('type'));
    }

    public function test_training_context_with_an_unknown_type_ignores_preferences(): void
    {
        $user = $this->userWithToken('tok', ['trainer_upcoming' => false, 'trainer_cancel_alert' => false, 'participant_upcoming' => false]);
        $training = TrainingFactory::new()->create();

        $this->artisan('notification:sendToUsersTrainingContext', [
            'userIds' => (string) $user->id, 'trainingId' => $training->id, 'title' => 't', 'data' => 'd', 'notificationType' => 9,
        ])->assertSuccessful();

        $this->assertSame(['tok'], $this->allTokens());
    }

    public function test_training_context_with_store_status_notifies_each_user_only_once(): void
    {
        $user = $this->userWithToken('tok');
        $training = TrainingFactory::new()->create();
        $args = ['userIds' => (string) $user->id, 'trainingId' => $training->id, 'title' => 't', 'data' => 'd', 'notificationType' => 3, '--storeStatus' => true];

        $this->artisan('notification:sendToUsersTrainingContext', $args)->assertSuccessful();
        $this->artisan('notification:sendToUsersTrainingContext', $args)->assertSuccessful();

        $this->assertCount(1, $this->sent);
        $this->assertSame(1, UserTrainingNotification::where('user_id', $user->id)->where('training_id', $training->id)->count());
    }

    public function test_training_context_does_not_store_status_without_the_flag(): void
    {
        $user = $this->userWithToken('tok');
        $training = TrainingFactory::new()->create();
        $args = ['userIds' => (string) $user->id, 'trainingId' => $training->id, 'title' => 't', 'data' => 'd', 'notificationType' => 3];

        $this->artisan('notification:sendToUsersTrainingContext', $args)->assertSuccessful();
        $this->artisan('notification:sendToUsersTrainingContext', $args)->assertSuccessful();

        $this->assertCount(2, $this->sent);
        $this->assertSame(0, UserTrainingNotification::count());
    }

    // ---- reminder commands -------------------------------------------------------

    private function soonTraining(): Training
    {
        return TrainingFactory::new()->between(now()->addHours(2)->format('Y-m-d H:i:s'), now()->addHours(3)->format('Y-m-d H:i:s'))
            ->create(['location_id' => LocationFactory::new()->create(['name' => 'Halle 1'])->id]);
    }

    public function test_participant_reminder_goes_to_attending_participants_with_participant_upcoming_enabled(): void
    {
        $training = $this->soonTraining();
        $attending = $this->userWithToken('attends');
        $disabled = $this->userWithToken('disabled', ['participant_upcoming' => false]);
        $declined = $this->userWithToken('declined');
        $training->participants()->attach($attending->id, ['attend' => 1]);
        $training->participants()->attach($disabled->id, ['attend' => 1]);
        $training->participants()->attach($declined->id, ['attend' => 0]);

        $this->artisan('notification:upcomingTraining')->assertSuccessful();

        $this->assertSame(['attends'], $this->allTokens());
        $this->assertSame('Erinnerung: Dein Training startet in Kürze', $this->sent[0]['title']);
        $this->assertStringContainsString('Ort: Halle 1', $this->sent[0]['body']);
    }

    public function test_reminders_are_sent_once_per_training_even_with_several_trainings_in_the_window(): void
    {
        $a = $this->soonTraining();
        $b = $this->soonTraining();
        $user = $this->userWithToken('tok');
        $a->participants()->attach($user->id, ['attend' => 1]);
        $b->participants()->attach($user->id, ['attend' => 1]);

        // the scheduler runs this every 30 minutes
        $this->artisan('notification:upcomingTraining')->assertSuccessful();
        $this->artisan('notification:upcomingTraining')->assertSuccessful();
        $this->artisan('notification:upcomingTraining')->assertSuccessful();

        $this->assertCount(2, $this->sent, 'one push per training, not one per run');
        $this->assertSame(2, UserTrainingNotification::where('user_id', $user->id)->count());
    }

    public function test_reminders_skip_deleted_trainings(): void
    {
        $training = $this->soonTraining();
        $training->deleted = 1;
        $training->save();
        $user = $this->userWithToken('tok');
        $training->participants()->attach($user->id, ['attend' => 1]);
        $training->trainers()->attach($user->id);

        $this->artisan('notification:upcomingTraining')->assertSuccessful();
        $this->artisan('notification:upcomingTrainingForTrainer')->assertSuccessful();

        $this->assertCount(0, $this->sent);
    }

    public function test_participant_reminder_works_for_trainings_without_a_location(): void
    {
        $training = TrainingFactory::new()->between(now()->addHours(2)->format('Y-m-d H:i:s'), now()->addHours(3)->format('Y-m-d H:i:s'))->create(['location_id' => null]);
        $user = $this->userWithToken('tok');
        $training->participants()->attach($user->id, ['attend' => 1]);

        $this->artisan('notification:upcomingTraining')->assertSuccessful();

        $this->assertCount(1, $this->sent);
    }

    public function test_trainer_reminder_goes_to_trainers_with_trainer_upcoming_enabled(): void
    {
        $training = $this->soonTraining();
        $on = $this->userWithToken('trainer-on');
        $off = $this->userWithToken('trainer-off', ['trainer_upcoming' => false]);
        $training->trainers()->attach([$on->id, $off->id]);
        $p = UserFactory::new()->create();
        $training->participants()->attach($p->id, ['attend' => 1]);

        $this->artisan('notification:upcomingTrainingForTrainer')->assertSuccessful();

        $this->assertSame(['trainer-on'], $this->allTokens());
        $this->assertStringContainsString('Es haben sich 1 Sportler angemeldet', $this->sent[0]['body']);
    }

    // ---- flows triggered through the HTTP API ------------------------------------

    public function test_cancelling_within_24_hours_alerts_trainers_who_enabled_trainer_cancel_alert(): void
    {
        $group = GroupFactory::new()->create();
        $on = $this->userWithToken('trainer-on');
        $off = $this->userWithToken('trainer-off', ['trainer_cancel_alert' => false]);
        $member = UserFactory::new()->inGroups([$group])->create(['firstName' => 'Kim', 'familyName' => 'Kind']);
        $training = TrainingFactory::new()
            ->between(now()->addHours(5)->format('Y-m-d H:i:s'), now()->addHours(6)->format('Y-m-d H:i:s'))
            ->forGroups([$group])->withTrainers([$on, $off])->create();

        $this->postJson("/api/v1/training/{$training->id}/checkoutunregistered/{$member->id}", ['reason' => 'krank'])
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $this->assertSame(['trainer-on'], $this->allTokens());
        $this->assertStringStartsWith('Absage für das Training um ', $this->sent[0]['title']);
        $this->assertStringContainsString('Sportler: Kim Kind', $this->sent[0]['body']);
        $this->assertStringContainsString('Grund: krank', $this->sent[0]['body']);
        $this->assertStringEndsWith('/#/trainingsPrepare', $this->sent[0]['data']['click_action']);
    }

    public function test_absence_alerts_trainers_of_the_branch_who_enabled_absence_alert(): void
    {
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['branch_id' => $branch->id]);
        $member = UserFactory::new()->inGroups([$group])->create(['firstName' => 'Max', 'familyName' => 'Muster']);
        $on = UserFactory::new()->role('trainer')->trainerOfBranches([$branch])->create();
        $off = UserFactory::new()->role('trainer')->trainerOfBranches([$branch])->create(['notification_preferences' => ['absence_alert' => false]]);
        $foreign = UserFactory::new()->role('trainer')->trainerOfBranches([BranchFactory::new()->create()])->create();
        $this->device($on, 'trainer-on');
        $this->device($off, 'trainer-off');
        $this->device($foreign, 'trainer-foreign');

        $this->postJson("/api/v1/simpleuser/{$member->id}/storeAbsence", [
            'absenceStart' => '2030-08-01T00:00:00+0200', 'absenceEnd' => '2030-08-14T00:00:00+0200', 'absenceReason' => 'Urlaub',
        ])->assertOk();

        $this->assertSame(['trainer-on'], $this->allTokens());
        $this->assertSame('Abwesenheit Max Muster', $this->sent[0]['title']);
        $this->assertStringContainsString('Von 01.08.30 bis 14.08.30', $this->sent[0]['body']);
        $this->assertStringContainsString('Grund: Urlaub', $this->sent[0]['body']);
    }

    // ---- notification:test -------------------------------------------------------

    public function test_the_test_command_sends_to_the_given_token(): void
    {
        $this->artisan('notification:test', ['token' => 'device-1', 'title' => 'Titel', 'text' => 'Text'])
            ->expectsOutputToContain('Message successfully sent to 1 recipients')
            ->assertSuccessful();

        $this->assertSame(['device-1'], $this->sent[0]['tokens']);
        $this->assertSame('Titel', $this->sent[0]['data']['title']);
        $this->assertSame('Text', $this->sent[0]['data']['body']);
    }
}
