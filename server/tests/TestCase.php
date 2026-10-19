<?php

namespace Tests;

use App\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use LaravelFCM\Facades\FCM;
use LaravelFCM\Response\DownstreamResponse;
use Mockery;
use Mockery\MockInterface;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Support\SeedRolesAndPermissions;

/**
 * Base class for all backend tests.
 *
 * - runs against the real MySQL test database (see phpunit.xml / `make test-db`)
 * - seeds the production roles/permissions
 * - mocks Firebase Cloud Messaging and fakes mail so nothing leaves the process
 */
abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use RefreshDatabase;

    /** The FCM facade mock (push notifications). */
    protected MockInterface $fcm;

    /** The fake FCM downstream response. */
    protected MockInterface $fcmResponse;

    protected function setUp(): void
    {
        parent::setUp();

        (new SeedRolesAndPermissions)->run();

        Mail::fake();

        $this->fcmResponse = Mockery::mock(DownstreamResponse::class);
        $this->fcmResponse->shouldReceive('numberSuccess')->andReturn(1)->byDefault();
        $this->fcmResponse->shouldReceive('numberFailure')->andReturn(0)->byDefault();
        $this->fcmResponse->shouldReceive('tokensToDelete')->andReturn([])->byDefault();

        $this->fcm = FCM::shouldReceive('sendTo')->andReturn($this->fcmResponse)->byDefault()->getMock();
    }

    /**
     * The JWT guard and the tymon/jwt token holder are long-lived singletons, so
     * without this a user authenticated by one request would leak into the next
     * request of the same test.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('tymon.jwt');
        $this->app->forgetInstance('tymon.jwt.auth');

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

    protected function jwtFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    /** Authenticate subsequent requests like the browser does: HttpOnly `jwt_token` cookie. */
    protected function actingAsJwt(User $user): static
    {
        return $this->withUnencryptedCookie('jwt_token', $this->jwtFor($user));
    }

    /** Authenticate subsequent requests with an Authorization bearer header. */
    protected function actingAsBearer(User $user): static
    {
        return $this->withToken($this->jwtFor($user));
    }
}
