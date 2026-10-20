<?php

namespace Tests\Feature\Notifications;

use App\NotificationToken;
use App\Services\FirebaseMessagingService;
use App\User;
use Database\Factories\BranchFactory;
use Database\Factories\GroupFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

/** notification:birthdays. Firebase is replaced by an overload mock (one process per test), nothing is sent. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class BirthdayNotificationsTest extends TestCase
{
    /** @var array<int, array{tokens: array, title: string, body: string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2027-10-20 07:00:00');
        $firebase = Mockery::mock('overload:'.FirebaseMessagingService::class);
        $firebase->shouldReceive('sendToDevices')->andReturnUsing(function ($tokens, $title, $body) {
            $this->sent[] = ['tokens' => (array) $tokens, 'title' => $title, 'body' => $body];

            return ['success' => 1, 'failure' => 0, 'tokensToDelete' => [], 'tokensToRetry' => []];
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function trainer(string $token, array $branches, array $attrs = []): User
    {
        $trainer = UserFactory::new()->role('trainer')->trainerOfBranches($branches)->create($attrs);
        $t = new NotificationToken;
        $t->user_id = $trainer->id;
        $t->token = $token;
        $t->save();

        return $trainer;
    }

    private function kid(string $first, string $family, string $birthdate, array $groups, array $attrs = []): User
    {
        return UserFactory::new()->inGroups($groups)->create(array_merge(['firstName' => $first, 'familyName' => $family, 'birthdate' => $birthdate], $attrs));
    }

    private function run7(): void
    {
        $this->artisan('notification:birthdays')->assertSuccessful();
    }

    public function test_each_trainer_gets_one_push_with_the_full_names_of_the_birthdays_in_their_groups(): void
    {
        $branchA = BranchFactory::new()->create();
        $branchB = BranchFactory::new()->create();
        $groupA1 = GroupFactory::new()->create(['branch_id' => $branchA->id]);
        $groupA2 = GroupFactory::new()->create(['branch_id' => $branchA->id]);
        $groupB = GroupFactory::new()->create(['branch_id' => $branchB->id]);
        $this->trainer('tok-a', [$branchA]);
        $this->trainer('tok-b', [$branchB]);
        $this->trainer('tok-ab', [$branchA, $branchB]);
        $this->trainer('tok-none', []);
        $this->kid('Mia', 'Schäfer', '2015-10-20', [$groupA1]);
        $this->kid('Leo', 'Klein', '2012-10-20', [$groupA2]);
        $this->kid('Ben', 'Weber', '2013-10-20', [$groupB]);
        $this->kid('Nina', 'Alt', '2014-10-21', [$groupA1]);                 // tomorrow
        $this->kid('Paul', 'Inaktiv', '2014-10-20', [$groupA1], ['active' => 0]);
        $this->kid('Zoe', 'Ohne', '2014-10-20', []);                          // in no group

        $this->run7();

        $byToken = collect($this->sent)->mapWithKeys(fn ($s) => [$s['tokens'][0] => $s]);
        $this->assertEqualsCanonicalizing(['tok-a', 'tok-b', 'tok-ab'], $byToken->keys()->all(), 'one push per trainer, none for a trainer without groups');
        $this->assertCount(3, $this->sent);
        $this->assertStringContainsString('Mia Schäfer', $byToken['tok-a']['body']);
        $this->assertStringContainsString('Leo Klein', $byToken['tok-a']['body']);
        $this->assertStringNotContainsString('Ben Weber', $byToken['tok-a']['body']);
        $this->assertSame('Heute hat Geburtstag: Ben Weber', $byToken['tok-b']['body']);
        foreach (['Mia Schäfer', 'Leo Klein', 'Ben Weber'] as $name) {
            $this->assertStringContainsString($name, $byToken['tok-ab']['body']);
        }
        foreach ($this->sent as $s) {
            $this->assertStringNotContainsString('Nina', $s['body']);
            $this->assertStringNotContainsString('Paul', $s['body']);
            $this->assertStringNotContainsString('Zoe', $s['body']);
        }
    }

    public function test_nothing_is_sent_without_birthdays_in_the_groups_of_a_trainer(): void
    {
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['branch_id' => $branch->id]);
        $this->trainer('tok', [$branch]);
        $this->kid('Nina', 'Alt', '2014-10-21', [$group]);

        $this->run7();

        $this->assertSame([], $this->sent);
    }

    public function test_a_trainer_with_the_preference_off_gets_nothing(): void
    {
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['branch_id' => $branch->id]);
        $this->trainer('tok-off', [$branch], ['notification_preferences' => ['trainer_birthday' => false]]);
        $this->trainer('tok-on', [$branch]);
        $this->kid('Mia', 'Schäfer', '2015-10-20', [$group]);

        $this->run7();

        $this->assertSame(['tok-on'], array_merge(...array_column($this->sent, 'tokens')));
    }

    public function test_a_trainers_own_birthday_is_not_announced_to_themselves(): void
    {
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['branch_id' => $branch->id]);
        $trainer = $this->trainer('tok', [$branch], ['birthdate' => '1990-10-20']);
        $trainer->groups()->attach($group->id);

        $this->run7();

        $this->assertSame([], $this->sent);
    }

    public function test_a_long_list_is_shortened(): void
    {
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['branch_id' => $branch->id]);
        $this->trainer('tok', [$branch]);
        foreach (range(1, 10) as $i) {
            $this->kid("Kind$i", 'Test', '2015-10-20', [$group]);
        }

        $this->run7();

        $this->assertStringContainsString('und 2 weitere', $this->sent[0]['body']);
    }

    public function test_29_february_is_celebrated_on_28_february_in_non_leap_years(): void
    {
        Carbon::setTestNow('2027-02-28 07:00:00');
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['branch_id' => $branch->id]);
        $this->trainer('tok', [$branch]);
        $this->kid('Schalt', 'Jahr', '2016-02-29', [$group]);

        $this->run7();

        $this->assertStringContainsString('Schalt Jahr', $this->sent[0]['body']);
    }
}
