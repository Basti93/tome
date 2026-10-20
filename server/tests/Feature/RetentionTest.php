<?php

namespace Tests\Feature;

use App\Services\RetentionService;
use App\Training;
use App\User;
use Carbon\CarbonImmutable;
use Database\Factories\BranchFactory;
use Database\Factories\GroupFactory;
use Database\Factories\TrainingFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * tome:retention. "now" is fixed; every boundary is tested one second on each side.
 */
class RetentionTest extends TestCase
{
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = CarbonImmutable::parse('2030-06-15 12:00:00');
        Carbon::setTestNow($this->now);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---- helpers -------------------------------------------------------------------

    private function format(CarbonImmutable $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /** A user that qualifies for deletion unless the test gives a reason to keep it. */
    private function staleUser(array $attributes = [], ?string $role = null, ?CarbonImmutable $createdAt = null): User
    {
        $user = $this->makeUser($role, $attributes);
        DB::table('users')->where('id', $user->id)->update([
            'created_at' => $this->format($createdAt ?? $this->now->subMonths(30)),
            'updated_at' => $this->format($this->now->subMonths(30)),
        ]);

        return $user->refresh();
    }

    private function training(CarbonImmutable $start, bool $evaluated = true, bool $deleted = false): Training
    {
        return TrainingFactory::new()->between($this->format($start), $this->format($start->addMinutes(90)))
            ->create(['evaluated' => $evaluated ? 1 : 0, 'deleted_at' => $deleted ? now() : null]);
    }

    private function participate(Training $training, User $user, ?int $attend = 1, ?string $cancelReason = null): void
    {
        DB::table('training_participation')->insert([
            'training_id' => $training->id, 'user_id' => $user->id, 'attend' => $attend, 'cancelreason' => $cancelReason,
        ]);
    }

    private function userExists(User $user): bool
    {
        return DB::table('users')->where('id', $user->id)->exists();
    }

    private function retain(bool $dryRun = false): array
    {
        return app(RetentionService::class)->run($dryRun, $this->now);
    }

    private function cutoff(int $months): CarbonImmutable
    {
        return $this->now->subMonths($months);
    }

    // ---- user deletion -------------------------------------------------------------

    public function test_a_user_without_recent_activity_is_deleted_with_everything_attached(): void
    {
        $user = $this->staleUser(['email' => 'old@example.com', 'profile_image_name' => 'profile_images/'.str_repeat('a', 40).'.jpg'], 'member');
        Storage::disk('public')->put('profile_images/'.str_repeat('a', 40).'.jpg', 'img');
        $group = GroupFactory::new()->create();
        $branch = BranchFactory::new()->create();
        $user->groups()->attach($group->id);
        $user->trainerBranches()->attach($branch->id);
        $user->givePermissionTo('read-user');
        // old activity (outside both windows)
        $oldTraining = $this->training($this->cutoff(25));
        $this->participate($oldTraining, $user);
        $oldTraining->trainers()->attach($user->id);
        DB::table('user_notification_token')->insert(['user_id' => $user->id, 'token' => 'tok', 'created_at' => $this->format($this->now), 'updated_at' => $this->format($this->now)]);
        DB::table('user_training_notification')->insert(['user_id' => $user->id, 'training_id' => $oldTraining->id, 'type' => 1]);
        DB::table('auth_logs')->insert(['user_id' => $user->id, 'action' => 'login', 'created_at' => $this->format($this->now)]);
        DB::table('password_resets')->insert(['email' => 'old@example.com', 'token' => 'x', 'created_at' => $this->format($this->now)]);
        DB::table('password_invites')->insert(['email' => 'old@example.com', 'token' => 'x', 'created_at' => $this->format($this->now)]);

        $keeper = $this->makeUser('member');
        $keeperGroup = GroupFactory::new()->create();
        $keeper->groups()->attach($keeperGroup->id);

        $counts = $this->retain();

        $this->assertSame(1, $counts['users']);
        $this->assertSame(1, $counts['profile_images']);
        $this->assertFalse($this->userExists($user));
        foreach (['training_participation', 'training_trainer', 'user_group', 'trainer_branch', 'user_notification_token', 'user_training_notification', 'auth_logs'] as $table) {
            $this->assertSame(0, DB::table($table)->where('user_id', $user->id)->count(), "$table must be cleaned");
        }
        $this->assertSame(0, DB::table('model_has_roles')->where('model_id', $user->id)->where('model_type', User::class)->count());
        $this->assertSame(0, DB::table('model_has_permissions')->where('model_id', $user->id)->where('model_type', User::class)->count());
        $this->assertSame(0, DB::table('password_resets')->where('email', 'old@example.com')->count());
        $this->assertSame(0, DB::table('password_invites')->where('email', 'old@example.com')->count());
        Storage::disk('public')->assertMissing('profile_images/'.str_repeat('a', 40).'.jpg');

        // the recently created user (fixture user keeper) and its pivots are untouched
        $this->assertTrue($this->userExists($keeper));
        $this->assertSame(1, DB::table('user_group')->where('user_id', $keeper->id)->count());

        // only personal data goes: groups and branches survive, only the memberships of the deleted user disappear
        $this->assertTrue(DB::table('groups')->where('id', $group->id)->exists());
        $this->assertTrue(DB::table('groups')->where('id', $keeperGroup->id)->exists());
        $this->assertTrue(DB::table('branches')->where('id', $branch->id)->exists());
        $this->assertTrue(DB::table('trainings')->where('id', $oldTraining->id)->exists());
        $this->assertSame(0, DB::table('user_group')->where('group_id', $group->id)->count());
        $this->assertSame(1, DB::table('user_group')->where('group_id', $keeperGroup->id)->count());
    }

    public function test_the_inactivity_window_is_configurable(): void
    {
        $user12 = $this->staleUser(createdAt: $this->cutoff(40));
        $user24 = $this->staleUser(createdAt: $this->cutoff(40));
        // last attended 18 months ago: inactive for N=12, still active for N=24
        $this->participate($this->training($this->cutoff(18)), $user12);
        $this->participate($this->training($this->cutoff(18)), $user24);

        config(['tome.retention.inactive_months' => 24]);
        $this->assertSame(0, $this->retain(dryRun: true)['users']);

        config(['tome.retention.inactive_months' => 12]);
        $this->assertSame(2, $this->retain(dryRun: true)['users']);
        $this->assertSame(2, $this->retain()['users']);
        $this->assertFalse($this->userExists($user12));
        $this->assertFalse($this->userExists($user24));
    }

    public function test_the_account_age_uses_the_configured_window_too(): void
    {
        $young = $this->staleUser(createdAt: $this->cutoff(10));
        $old = $this->staleUser(createdAt: $this->cutoff(14));

        config(['tome.retention.inactive_months' => 12]);
        $this->retain();

        $this->assertTrue($this->userExists($young));
        $this->assertFalse($this->userExists($old));
    }

    public function test_attended_participation_in_an_evaluated_training_protects_for_24_months(): void
    {
        $atBoundary = $this->staleUser();
        $beyond = $this->staleUser();
        $this->participate($this->training($this->cutoff(24)), $atBoundary);
        $this->participate($this->training($this->cutoff(24)->subSecond()), $beyond);

        $counts = $this->retain();

        $this->assertTrue($this->userExists($atBoundary), 'a training exactly 24 months ago still counts as recent');
        $this->assertFalse($this->userExists($beyond), 'one second older does not');
        $this->assertSame(1, $counts['users']);
    }

    public function test_only_attended_evaluated_non_deleted_participations_protect_a_user(): void
    {
        $recent = $this->now->subMonths(2);
        $declined = $this->staleUser();
        $notEvaluated = $this->staleUser();
        $deletedTraining = $this->staleUser();
        $nullAttend = $this->staleUser();
        $protected = $this->staleUser();
        $this->participate($this->training($recent), $declined, 0);
        $this->participate($this->training($recent, evaluated: false), $notEvaluated);
        $this->participate($this->training($recent, deleted: true), $deletedTraining);
        $this->participate($this->training($recent), $nullAttend, null);
        $this->participate($this->training($recent), $protected);

        $this->retain();

        $this->assertFalse($this->userExists($declined));
        $this->assertFalse($this->userExists($notEvaluated));
        $this->assertFalse($this->userExists($deletedTraining));
        $this->assertFalse($this->userExists($nullAttend));
        $this->assertTrue($this->userExists($protected));
    }

    public function test_a_trainer_assignment_protects_for_12_months_including_future_trainings(): void
    {
        $atBoundary = $this->staleUser();
        $beyond = $this->staleUser();
        $upcoming = $this->staleUser();
        $onDeleted = $this->staleUser();
        $this->training($this->cutoff(12))->trainers()->attach($atBoundary->id);
        $this->training($this->cutoff(12)->subSecond())->trainers()->attach($beyond->id);
        $this->training($this->now->addDays(10), evaluated: false)->trainers()->attach($upcoming->id);
        $this->training($this->now->subMonth(), deleted: true)->trainers()->attach($onDeleted->id);

        $this->retain();

        $this->assertTrue($this->userExists($atBoundary));
        $this->assertFalse($this->userExists($beyond));
        $this->assertTrue($this->userExists($upcoming));
        $this->assertFalse($this->userExists($onDeleted), 'assignments on deleted trainings do not count');
    }

    public function test_admins_and_trainers_are_never_deleted_but_members_are(): void
    {
        $admin = $this->staleUser([], 'admin');
        $trainer = $this->staleUser([], 'trainer');
        $member = $this->staleUser([], 'member');
        $noRole = $this->staleUser();

        $counts = $this->retain();

        $this->assertTrue($this->userExists($admin));
        $this->assertTrue($this->userExists($trainer), 'trainers are kept regardless of their last assignment');
        $this->assertFalse($this->userExists($member));
        $this->assertFalse($this->userExists($noRole));
        $this->assertSame(2, $counts['users']);
    }

    public function test_accounts_younger_than_24_months_are_kept_and_unknown_age_is_deleted(): void
    {
        $exactly = $this->staleUser(createdAt: $this->cutoff(24));
        $older = $this->staleUser(createdAt: $this->cutoff(24)->subSecond());
        $younger = $this->staleUser(createdAt: $this->cutoff(24)->addSecond());
        $unknown = $this->staleUser();
        DB::table('users')->where('id', $unknown->id)->update(['created_at' => null]);

        $this->retain();

        $this->assertTrue($this->userExists($exactly), 'created exactly 24 months ago is not older than 24 months');
        $this->assertFalse($this->userExists($older));
        $this->assertTrue($this->userExists($younger));
        $this->assertFalse($this->userExists($unknown));
    }

    public function test_only_profile_images_matching_the_naming_scheme_are_removed_from_disk(): void
    {
        $valid = 'profile_images/'.str_repeat('v', 40).'.png';
        Storage::disk('public')->put($valid, 'x');
        Storage::disk('public')->put('keep.txt', 'x');
        Storage::disk('public')->put('profile_images/legacy.jpg', 'x');
        $this->staleUser(['profile_image_name' => $valid]);
        $this->staleUser(['profile_image_name' => '../keep.txt']);
        $this->staleUser(['profile_image_name' => 'profile_images/legacy.jpg']);

        $counts = $this->retain();

        $this->assertSame(3, $counts['users']);
        $this->assertSame(1, $counts['profile_images']);
        Storage::disk('public')->assertMissing($valid);
        Storage::disk('public')->assertExists('keep.txt');
        Storage::disk('public')->assertExists('profile_images/legacy.jpg');
    }

    // ---- independent clean-ups -----------------------------------------------------

    public function test_cancel_reasons_older_than_12_months_are_nulled(): void
    {
        $active = $this->makeUser('member');
        $old = $this->training($this->cutoff(12)->subSecond());
        $boundary = $this->training($this->cutoff(12));
        $recent = $this->training($this->cutoff(11));
        foreach ([$old, $boundary, $recent] as $training) {
            $this->participate($training, $active, 0, 'krank');
        }

        $counts = $this->retain();

        $reasons = DB::table('training_participation')->pluck('cancelreason', 'training_id');
        $this->assertNull($reasons[$old->id]);
        $this->assertSame('krank', $reasons[$boundary->id], 'a training exactly 12 months ago keeps its reason');
        $this->assertSame('krank', $reasons[$recent->id]);
        $this->assertSame(1, $counts['cancel_reasons']);
        $this->assertSame(3, DB::table('training_participation')->count(), 'the rows themselves stay');
    }

    public function test_participations_older_than_36_months_are_deleted(): void
    {
        $active = $this->makeUser('member');
        $old = $this->training($this->cutoff(36)->subSecond());
        $boundary = $this->training($this->cutoff(36));
        $this->participate($old, $active, 1, 'grund');
        $this->participate($boundary, $active);

        $counts = $this->retain();

        $this->assertSame([$boundary->id], DB::table('training_participation')->pluck('training_id')->all());
        $this->assertSame(1, $counts['participations']);
        $this->assertSame(0, $counts['cancel_reasons'], 'the old row is deleted, so its reason is not counted as nulled');
        $this->assertTrue(DB::table('trainings')->where('id', $old->id)->exists(), 'trainings are kept');
    }

    public function test_deleted_trainings_older_than_12_months_are_hard_deleted_with_their_pivots(): void
    {
        $user = $this->makeUser('member');
        $group = GroupFactory::new()->create();
        $stale = $this->training($this->cutoff(12)->subSecond(), deleted: true);
        $boundary = $this->training($this->cutoff(12), deleted: true);
        $liveOld = $this->training($this->cutoff(20));
        $stale->groups()->attach($group->id);
        $stale->trainers()->attach($user->id);
        $this->participate($stale, $user);
        DB::table('user_training_notification')->insert(['user_id' => $user->id, 'training_id' => $stale->id, 'type' => 3]);
        $liveOld->groups()->attach($group->id);

        $counts = $this->retain();

        $this->assertSame(1, $counts['trainings']);
        $this->assertFalse(DB::table('trainings')->where('id', $stale->id)->exists());
        $this->assertTrue(DB::table('trainings')->where('id', $boundary->id)->exists());
        $this->assertTrue(DB::table('trainings')->where('id', $liveOld->id)->exists(), 'only trainings with the deleted flag are removed');
        foreach (['training_group', 'training_trainer', 'training_participation', 'user_training_notification'] as $pivot) {
            $this->assertSame(0, DB::table($pivot)->where('training_id', $stale->id)->count(), "$pivot of the deleted training");
        }
        $this->assertSame(1, DB::table('training_group')->where('training_id', $liveOld->id)->count());
    }

    public function test_notification_tokens_not_updated_for_12_months_are_deleted(): void
    {
        $user = $this->makeUser('member');
        foreach ([['stale', $this->cutoff(12)->subSecond()], ['boundary', $this->cutoff(12)], ['fresh', $this->now]] as [$token, $updated]) {
            DB::table('user_notification_token')->insert([
                'user_id' => $user->id, 'token' => $token, 'created_at' => $this->format($this->cutoff(40)), 'updated_at' => $this->format($updated),
            ]);
        }

        $counts = $this->retain();

        $this->assertEqualsCanonicalizing(['boundary', 'fresh'], DB::table('user_notification_token')->pluck('token')->all());
        $this->assertSame(1, $counts['notification_tokens']);
    }

    public function test_auth_logs_older_than_90_days_are_deleted(): void
    {
        foreach ([['old', $this->now->subDays(90)->subSecond()], ['boundary', $this->now->subDays(90)], ['new', $this->now->subDay()]] as [$action, $at]) {
            DB::table('auth_logs')->insert(['user_id' => null, 'action' => $action, 'created_at' => $this->format($at)]);
        }

        $counts = $this->retain();

        $this->assertEqualsCanonicalizing(['boundary', 'new'], DB::table('auth_logs')->pluck('action')->all());
        $this->assertSame(1, $counts['auth_logs']);
    }

    // ---- dry run, transaction, command ---------------------------------------------

    private function seedEverything(): void
    {
        $user = $this->staleUser(['profile_image_name' => 'profile_images/'.str_repeat('d', 40).'.jpg'], 'member');
        Storage::disk('public')->put('profile_images/'.str_repeat('d', 40).'.jpg', 'img');
        $old = $this->training($this->cutoff(40));
        $this->participate($old, $user, 1, 'grund');
        $this->participate($this->training($this->cutoff(14)), $this->makeUser('member'), 0, 'grund');
        $this->training($this->cutoff(13), deleted: true);
        DB::table('user_notification_token')->insert(['user_id' => $user->id, 'token' => 't', 'created_at' => $this->format($this->cutoff(40)), 'updated_at' => $this->format($this->cutoff(40))]);
        DB::table('auth_logs')->insert(['user_id' => null, 'action' => 'login', 'created_at' => $this->format($this->now->subDays(200))]);
    }

    private function snapshot(): array
    {
        $snapshot = [];
        foreach (['users', 'trainings', 'training_participation', 'user_notification_token', 'auth_logs', 'model_has_roles'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy(DB::raw('1'))->get()->map(fn ($row) => (array) $row)->all();
        }

        return $snapshot;
    }

    public function test_dry_run_changes_nothing_and_reports_what_a_real_run_removes(): void
    {
        $this->seedEverything();
        $before = $this->snapshot();

        $dry = $this->retain(dryRun: true);

        $this->assertSame($before, $this->snapshot(), 'a dry run must not touch the database');
        Storage::disk('public')->assertExists('profile_images/'.str_repeat('d', 40).'.jpg');
        $this->assertGreaterThan(0, array_sum($dry));

        $real = $this->retain();

        $this->assertSame($dry, $real);
        $this->assertNotSame($before, $this->snapshot());
        Storage::disk('public')->assertMissing('profile_images/'.str_repeat('d', 40).'.jpg');
    }

    public function test_a_second_run_finds_nothing_to_do(): void
    {
        $this->seedEverything();
        $this->retain();

        $this->assertSame(array_fill_keys(array_keys($this->retain(dryRun: true)), 0), $this->retain());
    }

    public function test_everything_is_rolled_back_when_a_step_fails(): void
    {
        $this->seedEverything();
        $before = $this->snapshot();
        DB::beforeExecuting(function (string $query) {
            if (str_contains($query, 'delete from `users`')) {
                throw new \RuntimeException('boom');
            }
        });

        try {
            $this->retain();
            $this->fail('the failure must surface');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame($before, $this->snapshot());
        Storage::disk('public')->assertExists('profile_images/'.str_repeat('d', 40).'.jpg');
    }

    public function test_the_command_prints_counts_only_and_never_names(): void
    {
        $this->staleUser(['firstName' => 'Geheimvorname', 'familyName' => 'Geheimnachname', 'email' => 'geheim@example.com']);

        $this->artisan('tome:retention', ['--dry-run' => true])
            ->expectsOutputToContain('dry run')
            ->expectsOutputToContain('users')
            ->doesntExpectOutputToContain('Geheim')
            ->assertSuccessful();
        $this->assertSame(1, DB::table('users')->where('firstName', 'Geheimvorname')->count());

        $this->artisan('tome:retention')
            ->expectsOutputToContain('finished')
            ->doesntExpectOutputToContain('Geheim')
            ->doesntExpectOutputToContain('geheim@')
            ->assertSuccessful();
        $this->assertSame(0, DB::table('users')->where('firstName', 'Geheimvorname')->count());
    }
}
