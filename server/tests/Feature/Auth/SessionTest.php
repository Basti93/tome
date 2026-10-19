<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * me / refresh / logout and how the JWT travels (HttpOnly cookie vs. Authorization header).
 */
class SessionTest extends TestCase
{
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

    public function test_refresh_endpoint_is_currently_broken_and_returns_500(): void
    {
        // BUG: RefreshController calls Auth::guard('api')->token(), which does not exist on
        // PHPOpenSourceSaver\JWTAuth\JWTGuard ("Method [token] does not exist"), so every
        // refresh attempt ends in a 500. This pins the current behavior; when the bug is fixed
        // (e.g. via ->getToken()) this test must be replaced by the skipped one below.
        $user = $this->makeUser('member');

        $this->actingAsJwt($user)->postJson('/api/v1/auth/refresh')->assertStatus(500);
    }

    public function test_refresh_issues_a_new_cookie_and_blacklists_the_old_token(): void
    {
        $this->markTestSkipped('BUG: /auth/refresh always returns 500 (JWTGuard::token() does not exist); intended behavior documented here.');

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

        $this->withJwtCookie($cookie->getValue())->getJson('/api/v1/auth/me')->assertOk();
        $this->withJwtCookie($oldToken)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_refresh_without_a_token_fails(): void
    {
        $status = $this->postJson('/api/v1/auth/refresh')->status();

        // characterization: the endpoint has no auth middleware, the JWT exception bubbles up
        $this->assertContains($status, [401, 500]);
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
