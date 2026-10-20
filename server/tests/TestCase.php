<?php

namespace Tests;

use App\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\SeedRolesAndPermissions;

/**
 * Base class for all backend tests.
 *
 * - runs against the real MySQL test database (see phpunit.xml / `make test-db`)
 * - seeds the production roles/permissions
 * - fakes mail (Firebase is mocked per test, see Tests\Feature\Notifications) so nothing leaves the process
 */
abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new SeedRolesAndPermissions)->run();

        Mail::fake();

    }

    /** The user of the session the following requests run in (see actingAsUser). */
    private ?User $actor = null;

    /**
     * The session guard is a long-lived singleton, so without this a user authenticated by one request would leak
     * into the next request of the same test: guards are reset and the acting user is set again per request.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();
        if ($this->actor) {
            $this->app['auth']->guard('api')->setUser($this->actor);
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /** Create a persisted user with the given spatie role (member | trainer | admin | null). */
    protected function makeUser(?string $role = null, array $attributes = []): User
    {
        $factory = UserFactory::new();
        if ($role !== null) {
            $factory = $factory->role($role);
        }

        return $factory->create($attributes);
    }

    /** Authenticate the following requests as this user (Sanctum SPA session, guard `api`). */
    protected function actingAsUser(User $user): static
    {
        $this->actor = $user;

        return $this;
    }

    /** Headers of a request from the SPA's own origin: Sanctum then starts the session (cookie + CSRF). */
    protected function fromSpa(): static
    {
        return $this->withHeaders(['Referer' => 'http://localhost', 'Origin' => 'http://localhost']);
    }
}
