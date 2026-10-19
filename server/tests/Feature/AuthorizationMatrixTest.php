<?php

namespace Tests\Feature;

use Database\Factories\BranchFactory;
use Database\Factories\GroupFactory;
use Database\Factories\LocationFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\TrainingSeriesFactory;
use Database\Factories\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Who may call which protected endpoint: anonymous -> 401, member / trainer / admin -> allowed or 403.
 *
 * Roles (RolesTableSeeder): admin = everything (Gate::before), trainer = training/user/series/group CRUD
 * except delete-user, member = read-training + checkin-training only.
 */
class AuthorizationMatrixTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string, 2: array<string, mixed>, 3: array{0: int, 1: int, 2: int, 3: int}}> */
    public static function endpoints(): array
    {
        $training = ['start' => '2099-09-01T18:00:00+0200', 'end' => '2099-09-01T19:30:00+0200', 'trainerIds' => [], 'groupIds' => []];
        $series = ['startTime' => '18:00:00', 'endTime' => '19:30:00', 'weekdays' => [1], 'deferUntil' => '2099-12-31T00:00:00+0100'];

        // [method, uri ({training} {user} {group} {location} {branch} {series} are replaced), body, [anonymous, member, trainer, admin]]
        return [
            'GET user index' => ['GET', '/api/v1/user', [], [401, 403, 200, 200]],
            'GET user trainer list' => ['GET', '/api/v1/user/trainer', [], [401, 403, 200, 200]],
            'POST user unregistered' => ['POST', '/api/v1/user/unregistered', ['firstName' => 'A', 'familyName' => 'B'], [401, 403, 201, 201]],
            'PUT user update' => ['PUT', '/api/v1/user/{user}', ['firstName' => 'A', 'familyName' => 'B'], [401, 403, 201, 201]],
            'DELETE user' => ['DELETE', '/api/v1/user/{user}', [], [401, 403, 403, 201]],
            'PUT user me' => ['PUT', '/api/v1/user/me', ['firstName' => 'A', 'familyName' => 'B'], [401, 201, 201, 201]],

            'GET training index' => ['GET', '/api/v1/training', [], [401, 200, 200, 200]],
            'GET training calendar' => ['GET', '/api/v1/training/calendar?start=2099-01-01&end=2099-01-31', [], [401, 200, 200, 200]],
            'POST training' => ['POST', '/api/v1/training', $training, [401, 403, 201, 201]],
            'PUT training' => ['PUT', '/api/v1/training/{training}', $training, [401, 403, 201, 201]],
            'DELETE training' => ['DELETE', '/api/v1/training/{training}', [], [401, 403, 201, 201]],
            'POST training prepared' => ['POST', '/api/v1/training/{training}/prepared', [], [401, 403, 200, 200]],
            'POST training checkin' => ['POST', '/api/v1/training/{training}/checkin/{user}', [], [401, 201, 201, 201]],
            'POST training checkout' => ['POST', '/api/v1/training/{training}/checkout/{user}', [], [401, 201, 201, 201]],

            'GET trainingevaluation trainer' => ['GET', '/api/v1/trainingevaluation/{user}', [], [401, 403, 200, 200]],
            'POST trainingevaluation evaluated' => ['POST', '/api/v1/trainingevaluation/{training}/evaluated', [], [401, 403, 200, 200]],
            'POST trainingevaluation addparticipant' => ['POST', '/api/v1/trainingevaluation/{training}/addparticipant/{user}', [], [401, 403, 200, 200]],
            'GET trainingprepare trainer' => ['GET', '/api/v1/trainingprepare/{user}', [], [401, 403, 200, 200]],

            'POST group' => ['POST', '/api/v1/group', ['name' => 'G', 'branchId' => '{branch}'], [401, 403, 201, 201]],
            'DELETE group' => ['DELETE', '/api/v1/group/{group}', [], [401, 403, 200, 200]],
            'POST location' => ['POST', '/api/v1/location', ['name' => 'L'], [401, 403, 403, 201]],
            'PUT location' => ['PUT', '/api/v1/location/{location}', ['name' => 'L'], [401, 403, 403, 201]],
            'POST branch' => ['POST', '/api/v1/branch', ['name' => 'B', 'shortName' => 'B'], [401, 403, 403, 201]],
            'PUT branch' => ['PUT', '/api/v1/branch/{branch}', ['name' => 'B', 'shortName' => 'B'], [401, 403, 403, 201]],
            'POST trainingSeries' => ['POST', '/api/v1/trainingSeries', $series, [401, 403, 201, 201]],
            'PUT trainingSeries' => ['PUT', '/api/v1/trainingSeries/{series}', $series, [401, 403, 201, 201]],
            'DELETE trainingSeries' => ['DELETE', '/api/v1/trainingSeries/{series}', [], [401, 403, 201, 201]],

            // Formerly open to every logged in member (security audit): now behind update-training / update-user / read-user / delete-training
            'POST trainingevaluation updateaccountingtime' => ['POST', '/api/v1/trainingevaluation/{training}/updateaccountingtime', ['trainerId' => '{user}', 'start' => '2099-01-01T18:00:00+0100', 'end' => '2099-01-01T19:00:00+0100'], [401, 403, 200, 200]],
            'GET trainingevaluation accountingtimestatistics' => ['GET', '/api/v1/trainingevaluation/accountingtimestatistics', [], [401, 403, 200, 200]],
            'POST trainingprepare updatecomment' => ['POST', '/api/v1/trainingprepare/{training}/updatecomment', ['comment' => 'x'], [401, 403, 200, 200]],
            'POST trainingprepare updatelocation' => ['POST', '/api/v1/trainingprepare/{training}/updatelocation', ['locationId' => null], [401, 403, 200, 200]],
            'POST trainingprepare updatecontent' => ['POST', '/api/v1/trainingprepare/{training}/updatecontent', ['contentIds' => []], [401, 403, 200, 200]],
            'POST trainingprepare updatetrainingtime' => ['POST', '/api/v1/trainingprepare/{training}/updatetrainingtime', ['start' => '2099-01-01T18:00:00+0100', 'end' => '2099-01-01T19:00:00+0100'], [401, 403, 200, 200]],
            'PATCH training deleteinfuture' => ['PATCH', '/api/v1/training/{training}/deleteinfuture', [], [401, 403, 200, 200]],
            'PUT user removeAbsence' => ['PUT', '/api/v1/user/{user}/removeAbsence', [], [401, 403, 200, 200]],
            'GET user allAbsence' => ['GET', '/api/v1/user/allAbsence', [], [401, 403, 200, 200]],
            'POST user invite' => ['POST', '/api/v1/user/{user}/invite', [], [401, 403, 200, 200]],
        ];
    }

    /** Fresh fixtures per request so destructive endpoints can be called once per role. */
    private function fixtures(): array
    {
        $group = GroupFactory::new()->create();
        $trainerForTraining = UserFactory::new()->inGroups([$group])->create();
        $training = TrainingFactory::new()->forGroups([$group])->withTrainers([$trainerForTraining])->create();

        return [
            '{training}' => $training->id,
            '{user}' => $trainerForTraining->id,
            '{group}' => GroupFactory::new()->create()->id,
            '{location}' => LocationFactory::new()->create()->id,
            '{branch}' => BranchFactory::new()->create()->id,
            '{series}' => TrainingSeriesFactory::new()->create()->id,
        ];
    }

    private function resolve(mixed $value, array $fixtures): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->resolve($v, $fixtures), $value);
        }
        if (is_string($value)) {
            // a bare placeholder becomes an integer id, embedded placeholders are replaced textually
            return isset($fixtures[$value]) ? (int) $fixtures[$value] : strtr($value, array_map('strval', $fixtures));
        }

        return $value;
    }

    #[DataProvider('endpoints')]
    public function test_endpoint_authorization(string $method, string $uri, array $body, array $expected): void
    {
        [$anonymous, $member, $trainer, $admin] = $expected;

        $send = function (?string $role) use ($method, $uri, $body) {
            $fixtures = $this->fixtures();
            // the acting user for "{user}"-style endpoints is separate from the fixture user
            $request = $role === null ? $this : $this->actingAsJwt($this->makeUser($role));

            return $request->json($method, $this->resolve($uri, $fixtures), $this->resolve($body, $fixtures));
        };

        $this->assertSame($anonymous, $send(null)->status(), 'anonymous');
        $this->assertSame($member, $send('member')->status(), 'member');
        $this->assertSame($trainer, $send('trainer')->status(), 'trainer');
        $this->assertSame($admin, $send('admin')->status(), 'admin');
    }

    public function test_routes_pointing_to_missing_controller_methods_were_removed(): void
    {
        $trainer = $this->makeUser('trainer');
        $training = TrainingFactory::new()->create();

        $this->actingAsJwt($trainer)->getJson('/api/v1/training/upcoming/trainer/1')->assertNotFound();
        $this->actingAsJwt($trainer)->getJson("/api/v1/training/{$training->id}/trainingscount/2099")->assertNotFound();
        $this->actingAsJwt($trainer)->postJson("/api/v1/trainingprepare/{$training->id}/prepared")->assertStatus(404);
    }

    public function test_debug_and_demo_routes_were_removed(): void
    {
        $this->getJson('/api/v1/hello')->assertNotFound();
        $this->getJson('/api/v1/mailtest')->assertNotFound();
        $this->actingAsJwt($this->makeUser('admin'))->getJson('/api/v1/refresh')->assertNotFound();
    }

    public function test_admin_passes_every_permission_check_without_holding_any_permission(): void
    {
        // Gate::before in AuthServiceProvider: the admin role carries no permissions in the database
        $admin = $this->makeUser('admin');
        $this->assertCount(0, $admin->getAllPermissions());
        $this->assertTrue($admin->can('update-training'));
        $this->assertTrue($admin->can('delete-user'));
        $this->assertFalse($this->makeUser('member')->can('update-training'));
    }
}
