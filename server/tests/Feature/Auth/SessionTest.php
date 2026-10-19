<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * me / refresh / logout and how the JWT travels (HttpOnly cookie vs. Authorization header).
 */
class SessionTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_me_with_cookie_returns_the_user(): void
    {
        $user = $this->makeUser('trainer');

        $this->actingAsJwt($user)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('roleNames', ['trainer'])
            ->assertJsonMissingPath('password');
    }

    public function test_me_with_bearer_header_returns_the_user(): void
    {
        $user = $this->makeUser('member');

        $this->actingAsBearer($user)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_bearer_header_wins_over_cookie(): void
    {
        $cookieUser = $this->makeUser('member');
        $bearerUser = $this->makeUser('member');

        $this->withJwtCookie($this->jwtFor($cookieUser))
            ->withToken($this->jwtFor($bearerUser))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $bearerUser->id);
    }

    public function test_garbage_cookie_is_unauthorized(): void
    {
        $this->withJwtCookie('not-a-jwt')
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/logout')->assertStatus(401);
    }

    public function test_logout_clears_the_cookie_and_invalidates_the_token(): void
    {
        $user = $this->makeUser('member');
        $token = $this->jwtFor($user);

        $response = $this->withJwtCookie($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertExactJson(['message' => 'Successfully logged out']);

        $cookie = $response->getCookie('jwt_token', false);
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isCleared(), 'cookie must be expired');
        $this->assertDatabaseHas('auth_logs', ['action' => 'logout', 'user_id' => $user->id]);

        // the blacklisted token no longer works
        $this->withJwtCookie($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_refresh_issues_a_new_cookie_and_blacklists_the_old_token(): void
    {
        $user = $this->makeUser('member');
        $oldToken = $this->jwtFor($user);

        $response = $this->withJwtCookie($oldToken)
            ->postJson('/api/v1/auth/refresh')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('expiresIn', 3600)
            ->assertJsonMissingPath('token');

        $cookie = $response->getCookie('jwt_token', false);
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertNotSame($oldToken, $cookie->getValue());
        $this->assertDatabaseHas('auth_logs', ['action' => 'token_refresh', 'user_id' => $user->id]);

        $this->withJwtCookie($cookie->getValue())->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('id', $user->id);
        $this->withJwtCookie($oldToken)->getJson('/api/v1/auth/me')->assertStatus(401);
        // the rotated-out token cannot be refreshed again either
        $this->withJwtCookie($oldToken)->postJson('/api/v1/auth/refresh')->assertStatus(401);
    }

    public function test_refresh_also_works_with_an_authorization_header(): void
    {
        $user = $this->makeUser('member');

        $this->withToken($this->jwtFor($user))->postJson('/api/v1/auth/refresh')->assertOk()->assertCookie('jwt_token');
    }

    public function test_an_expired_access_token_can_be_refreshed_inside_the_refresh_window(): void
    {
        $user = $this->makeUser('member');
        $token = $this->jwtFor($user);

        Carbon::setTestNow(now()->addMinutes(61));
        $this->withJwtCookie($token)->getJson('/api/v1/auth/me')->assertStatus(401);

        $response = $this->withJwtCookie($token)->postJson('/api/v1/auth/refresh')->assertOk();

        $fresh = $response->getCookie('jwt_token', false)->getValue();
        $this->withJwtCookie($fresh)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('id', $user->id);
    }

    public function test_a_token_outside_the_refresh_window_cannot_be_refreshed(): void
    {
        $token = $this->jwtFor($this->makeUser('member'));

        Carbon::setTestNow(now()->addMinutes((int) config('jwt.refresh_ttl') + 5));

        $this->withJwtCookie($token)->postJson('/api/v1/auth/refresh')
            ->assertStatus(401)->assertJsonPath('status', 'error');
    }

    public function test_a_logged_out_token_cannot_be_refreshed(): void
    {
        $token = $this->jwtFor($this->makeUser('member'));
        $this->withJwtCookie($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->withJwtCookie($token)->postJson('/api/v1/auth/refresh')->assertStatus(401);
    }

    public function test_refresh_without_or_with_a_garbage_token_is_401(): void
    {
        $this->postJson('/api/v1/auth/refresh')->assertStatus(401);
        $this->withJwtCookie('not-a-jwt')->postJson('/api/v1/auth/refresh')->assertStatus(401);
    }

    public function test_full_cookie_roundtrip_login_me_logout(): void
    {
        $user = $this->makeUser('trainer', ['email' => 'rt@example.com']);

        $login = $this->postJson('/api/v1/auth/login', ['email' => 'rt@example.com', 'password' => 'Password1!'])->assertOk();
        $jwt = $login->getCookie('jwt_token', false)->getValue();

        $this->withJwtCookie($jwt)->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('id', $user->id);
        $this->withJwtCookie($jwt)->postJson('/api/v1/auth/logout')->assertOk();
    }
}
