<?php

namespace Tests\Feature\Notifications;

use App\Mail\EvaluationReminder;
use App\NotificationToken;
use App\Services\FirebaseMessagingService;
use App\Training;
use App\User;
use Database\Factories\GroupFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

/** Firebase is replaced by an overload mock (see PushDispatchTest), hence one process per test. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class EvaluationReminderTest extends TestCase
{
    /** @var array<int, array{tokens: array, title: string, body: string, data: array}> */
    private array $sent = [];

    private int $pushSuccess = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $firebase = Mockery::mock('overload:'.FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToDevices')->andReturnUsing(function ($tokens, $title, $body, $data = []) {
            $this->sent[] = ['tokens' => (array) $tokens, 'title' => $title, 'body' => $body, 'data' => $data];

            return ['success' => $this->pushSuccess, 'failure' => 0, 'tokensToDelete' => [], 'tokensToRetry' => []];
        });
    }

    private function trainer(array $attributes = [], ?string $token = 'tok'): User
    {
        $user = UserFactory::new()->role('trainer')->create($attributes);
        if ($token) {
            $t = new NotificationToken;
            $t->user_id = $user->id;
            $t->token = $token;
            $t->save();
        }

        return $user;
    }

    /** A training that ended $hoursAgo hours ago (1.5h long). */
    private function trainingEnded(float $hoursAgo, User $trainer, array $attributes = []): Training
    {
        $end = now()->subMinutes((int) ($hoursAgo * 60));

        return TrainingFactory::new()
            ->between($end->copy()->subMinutes(90)->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'))
            ->withTrainers([$trainer])->create($attributes);
    }

    private function run20(): void
    {
        $this->artisan('notification:evaluation-reminder')->assertSuccessful();
    }

    public function test_selection_window_is_2_hours_to_7_days_after_the_end(): void
    {
        $trainer = $this->trainer();
        $tooRecent = $this->trainingEnded(1.9, $trainer);
        $justOld = $this->trainingEnded(2.1, $trainer);
        $oldest = $this->trainingEnded(24 * 6.9, $trainer);
        $tooOld = $this->trainingEnded(24 * 7.1, $trainer);

        $this->run20();

        $this->assertCount(1, $this->sent);
        $this->assertSame('2 Trainings noch nicht abgeschlossen', $this->sent[0]['title']);
        $this->assertSame(1, $justOld->fresh()->evaluation_reminder_count);
        $this->assertSame(1, $oldest->fresh()->evaluation_reminder_count);
        $this->assertNull($tooRecent->fresh()->evaluation_reminder_count);
        $this->assertNull($tooOld->fresh()->evaluation_reminder_count);
    }

    public function test_one_message_per_trainer_with_group_names_and_links_but_no_athlete_names(): void
    {
        config(['app.vue_url' => 'https://training.example']);
        $trainer = $this->trainer();
        $group = GroupFactory::new()->create(['name' => 'Anfaenger']);
        $athlete = UserFactory::new()->create(['firstName' => 'Ahmed', 'familyName' => 'Athlet']);
        $a = $this->trainingEnded(5, $trainer);
        $a->groups()->attach($group->id);
        $a->participants()->attach($athlete->id, ['attend' => 1]);
        $b = $this->trainingEnded(30, $trainer);

        $this->run20();

        $this->assertCount(1, $this->sent);
        $this->assertSame(['tok'], $this->sent[0]['tokens']);
        $this->assertStringContainsString('(Anfaenger)', $this->sent[0]['body']);
        $this->assertStringNotContainsString('Ahmed', $this->sent[0]['body'].$this->sent[0]['title']);
        $this->assertSame('https://training.example/#/trainingsEvaluation', $this->sent[0]['data']['click_action']);
        $this->assertNotNull($b->fresh()->evaluation_reminder_sent_at);
    }

    public function test_a_single_training_links_directly_to_the_quick_evaluation(): void
    {
        config(['app.vue_url' => 'https://training.example']);
        $trainer = $this->trainer();
        $t = $this->trainingEnded(5, $trainer);

        $this->run20();

        $this->assertSame('Training noch nicht abgeschlossen', $this->sent[0]['title']);
        $this->assertSame('https://training.example/#/quickEvaluation/'.$t->id, $this->sent[0]['data']['click_action']);
    }

    public function test_each_training_is_reminded_at_most_twice_three_days_apart(): void
    {
        $trainer = $this->trainer();
        $t = $this->trainingEnded(5, $trainer);

        $this->run20();
        $this->assertCount(1, $this->sent);

        // same day / next day: not yet
        $this->run20();
        $this->travel(2)->days();
        $this->run20();
        $this->assertCount(1, $this->sent);

        $this->travelBack();
        $t->forceFill(['evaluation_reminder_sent_at' => now()->subDays(3)->subHour()])->save();
        // still inside the 7 day window
        $this->run20();
        $this->assertCount(2, $this->sent);
        $this->assertSame(2, $t->fresh()->evaluation_reminder_count);

        $t->forceFill(['evaluation_reminder_sent_at' => now()->subDays(4)])->save();
        $this->run20();
        $this->assertCount(2, $this->sent, 'no third reminder');
    }

    public function test_push_is_preferred_and_email_is_the_fallback_but_never_both(): void
    {
        $withToken = $this->trainer(['email' => 'push@example.org']);
        $noToken = $this->trainer(['email' => 'mail@example.org'], null);
        $nothing = $this->trainer(['email' => ''], null);
        $this->trainingEnded(5, $withToken);
        $this->trainingEnded(5, $noToken);
        $this->trainingEnded(5, $nothing);

        $this->run20();

        $this->assertCount(1, $this->sent);
        $this->assertSame(['tok'], $this->sent[0]['tokens']);
        Mail::assertSent(EvaluationReminder::class, 1);
        Mail::assertSent(EvaluationReminder::class, fn ($m) => $m->hasTo('mail@example.org'));
    }

    public function test_failed_push_falls_back_to_email(): void
    {
        $this->pushSuccess = 0;
        $trainer = $this->trainer(['email' => 'mail@example.org']);
        $this->trainingEnded(5, $trainer);

        $this->run20();

        Mail::assertSent(EvaluationReminder::class, fn ($m) => $m->hasTo('mail@example.org'));
    }

    public function test_trainer_with_the_preference_off_is_skipped_and_the_training_not_counted(): void
    {
        $off = $this->trainer(['notification_preferences' => ['trainer_evaluation_reminder' => false]]);
        $t = $this->trainingEnded(5, $off);

        $this->run20();

        $this->assertSame([], $this->sent);
        Mail::assertNothingSent();
        $this->assertNull($t->fresh()->evaluation_reminder_count);
    }

    public function test_skips_evaluated_deleted_and_trainerless_trainings(): void
    {
        $trainer = $this->trainer();
        $this->trainingEnded(5, $trainer, ['evaluated' => 1]);
        $this->trainingEnded(5, $trainer, ['deleted' => 1]);
        $end = now()->subHours(5);
        TrainingFactory::new()->between($end->copy()->subMinutes(90)->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'))->create();

        $this->run20();

        $this->assertSame([], $this->sent);
        Mail::assertNothingSent();
    }
}
