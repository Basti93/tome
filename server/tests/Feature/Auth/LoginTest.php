<?php

namespace Tests\Feature\Auth;

use App\User;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Tests\TestCase;

class LoginTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function login(string $email, string $password = UserFactory::PASSWORD)
    {
        return $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
    }

    public function test_login_returns_user_and_sets_http_only_cookie_but_no_token_in_body(): void
    {
        $user = $this->makeUser('trainer', ['email' => 'trainer@example.com']);

        $response = $this->login('trainer@example.com')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'trainer@example.com')
            ->assertJsonPath('user.roleNames', ['trainer'])
            ->assertJsonPath('expiresIn', 3600)
            ->assertJsonMissingPath('token')
            ->assertJsonMissingPath('user.password');

        $cookie = $response->getCookie('jwt_token', false);
        $this->assertNotNull($cookie, 'jwt_token cookie must be set');
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', $cookie->getSameSite());
        $this->assertSame('/', $cookie->getPath());
        $this->assertFalse($cookie->isSecure(), 'cookie is only Secure in production');
        $this->assertCount(3, explode('.', $cookie->getValue()), 'cookie holds a JWT');
    }

    public function test_user_payload_exposes_expected_fields(): void
    {
        $this->makeUser('member', ['email' => 'm@example.com', 'firstName' => 'Max', 'familyName' => 'Muster']);

        $this->login('m@example.com')->assertOk()->assertJsonStructure([
            'user' => ['id', 'firstName', 'familyName', 'email', 'active', 'registered', 'roleNames', 'groupIds', 'trainerBranchIds', 'name'],
        ])->assertJsonPath('user.name', 'Max Muster');
    }

    public function test_login_resets_failed_attempts_and_writes_an_audit_log(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);
        $user->forceFill(['failed_login_attempts' => 3])->save();

        $this->login('m@example.com')->assertOk();

        $this->assertSame(0, (int) $user->fresh()->failed_login_attempts);
        $this->assertDatabaseHas('auth_logs', ['action' => 'login', 'user_id' => $user->id]);
    }

    public function test_wrong_password_is_rejected_with_403(): void
    {
        $this->makeUser('member', ['email' => 'm@example.com']);

        $this->login('m@example.com', 'WrongPass1!')
            ->assertStatus(403)
            ->assertExactJson(['status' => 'error', 'message' => 'Unauthorized'])
            ->assertCookieMissing('jwt_token');

        $this->assertDatabaseHas('auth_logs', ['action' => 'login_failed']);
    }

    public function test_unknown_email_is_rejected_with_403(): void
    {
        $this->login('nobody@example.com')
            ->assertStatus(403)
            ->assertExactJson(['status' => 'error', 'message' => 'Unauthorized']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_unverified_email_cannot_log_in(): void
    {
        UserFactory::new()->unverified()->create(['email' => 'new@example.com']);

        $this->login('new@example.com')
            ->assertStatus(403)
            ->assertJsonPath('code', 'email_not_verified')
            ->assertCookieMissing('jwt_token');
    }

    public function test_account_is_locked_after_five_failed_attempts(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);

        for ($i = 1; $i <= 4; $i++) {
            $this->login('m@example.com', 'WrongPass1!')
                ->assertStatus(403)
                ->assertJsonPath('message', 'Unauthorized');
        }
        $this->assertSame(4, (int) $user->fresh()->failed_login_attempts);

        // 5th failure locks the account
        $this->login('m@example.com', 'WrongPass1!')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Account locked due to too many failed login attempts. Please try again later.');

        $user->refresh();
        $this->assertSame(5, (int) $user->failed_login_attempts);
        $this->assertTrue($user->locked_until->isFuture());

        // even the correct password is refused while locked
        $this->login('m@example.com')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Account locked due to too many failed login attempts. Please try again later.')
            ->assertCookieMissing('jwt_token');
    }

    public function test_lock_expires_after_fifteen_minutes(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);
        Carbon::setTestNow('2030-01-01 12:00:00');
        $user->forceFill(['failed_login_attempts' => 5, 'locked_until' => Carbon::now()->addMinutes(15)])->save();

        $this->login('m@example.com')->assertStatus(403);

        Carbon::setTestNow('2030-01-01 12:15:01');
        $this->login('m@example.com')->assertOk();
        $this->assertSame(0, (int) $user->fresh()->failed_login_attempts);
        $this->assertNull($user->fresh()->locked_until);
    }

    public function test_login_is_throttled_to_ten_requests_per_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->login('nobody@example.com')->assertStatus(403);
        }

        $this->login('nobody@example.com')->assertStatus(429);
    }

    public function test_locked_attempts_for_unknown_user_do_not_create_users(): void
    {
        $this->login('nobody@example.com', 'x');
        $this->assertSame(0, User::count());
    }
}
