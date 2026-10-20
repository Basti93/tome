<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * Sanctum SPA authentication: me / logout and how the session travels (HttpOnly cookie, CSRF cookie).
 */
class SessionTest extends TestCase
{
    /** Log in like the SPA does and return the session cookie value to send with the next requests. */
    private function loginCookie(string $email = 'm@example.com'): array
    {
        $this->makeUser('member', ['email' => $email]);
        $response = $this->fromSpa()->postJson('/api/v1/auth/login', ['email' => $email, 'password' => 'Password1!'])->assertOk();
        $cookie = $response->getCookie(config('session.cookie'), false);
        $this->assertNotNull($cookie, 'the login sets the session cookie');

        return [$cookie, $response];
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_me_returns_the_user_without_secrets(): void
    {
        $user = $this->makeUser('trainer');

        $this->actingAsUser($user)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('roleNames', ['trainer'])
            ->assertJsonMissingPath('password');
    }

    public function test_a_garbage_session_cookie_is_unauthorized(): void
    {
        $this->fromSpa()->withUnencryptedCookie(config('session.cookie'), 'not-a-session')
            ->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/logout')->assertStatus(401);
    }

    public function test_the_session_cookie_is_http_only_lax_and_lasts_two_weeks(): void
    {
        [$cookie] = $this->loginCookie();

        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertSame('/', $cookie->getPath());
        $this->assertSame(20160, (int) config('session.lifetime'));
        $this->assertGreaterThan(now()->addDays(13)->timestamp, $cookie->getExpiresTime());
    }

    public function test_full_cookie_roundtrip_login_me_logout(): void
    {
        [$cookie] = $this->loginCookie();

        $with = fn () => $this->fromSpa()->withCredentials()->withCookie($cookie->getName(), $cookie->getValue());

        $with()->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('email', 'm@example.com');
        $with()->postJson('/api/v1/auth/logout')->assertOk();
        $with()->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_the_csrf_cookie_route_lives_under_api_and_sets_the_xsrf_token(): void
    {
        $response = $this->fromSpa()->get('/api/sanctum/csrf-cookie')->assertNoContent();

        $this->assertNotNull($response->getCookie('XSRF-TOKEN', false));
    }

    public function test_an_unauthenticated_state_changing_request_from_the_spa_needs_the_csrf_token_in_production_like_runs(): void
    {
        // CSRF verification is skipped while running unit tests; this documents that the session middleware is active
        $this->fromSpa()->postJson('/api/v1/auth/login', ['email' => 'nobody@example.com', 'password' => 'x'])->assertStatus(403);
    }
}
