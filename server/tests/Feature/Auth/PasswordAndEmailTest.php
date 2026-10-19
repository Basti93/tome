<?php

namespace Tests\Feature\Auth;

use App\Mail\ResetPassword;
use App\Mail\VerifyEmail;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordAndEmailTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_recovery_sends_a_reset_mail_for_a_known_user(): void
    {
        $this->makeUser('member', ['email' => 'm@example.com']);

        $this->postJson('/api/v1/auth/recovery', ['email' => 'm@example.com'])
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);

        Mail::assertSent(ResetPassword::class, fn ($mail) => $mail->hasTo('m@example.com'));
        $this->assertDatabaseHas('password_resets', ['email' => 'm@example.com']);
        $this->assertDatabaseHas('auth_logs', ['action' => 'password_reset_requested']);
    }

    public function test_recovery_for_unknown_email_is_404(): void
    {
        $this->postJson('/api/v1/auth/recovery', ['email' => 'nobody@example.com'])->assertStatus(404);
        Mail::assertNothingSent();
    }

    public function test_recovery_validates_the_email(): void
    {
        $this->postJson('/api/v1/auth/recovery', ['email' => 'not-an-email'])
            ->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_recovery_is_throttled_to_three_per_minute(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/recovery', ['email' => 'nobody@example.com'])->assertStatus(404);
        }
        $this->postJson('/api/v1/auth/recovery', ['email' => 'nobody@example.com'])->assertStatus(429);
    }

    public function test_reset_with_valid_token_changes_the_password(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/reset', [
            'token' => $token,
            'email' => 'm@example.com',
            'password' => 'NewSecret1!',
            'password_confirmation' => 'NewSecret1!',
        ])->assertOk()->assertExactJson(['status' => 'ok']);

        $this->assertDatabaseHas('auth_logs', ['action' => 'password_reset', 'user_id' => $user->id]);

        $this->postJson('/api/v1/auth/login', ['email' => 'm@example.com', 'password' => 'NewSecret1!'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['email' => 'm@example.com', 'password' => UserFactory::PASSWORD])->assertStatus(403);
    }

    public function test_reset_with_invalid_token_is_500(): void
    {
        $this->makeUser('member', ['email' => 'm@example.com']);

        $this->postJson('/api/v1/auth/reset', [
            'token' => 'invalid',
            'email' => 'm@example.com',
            'password' => 'NewSecret1!',
            'password_confirmation' => 'NewSecret1!',
        ])->assertStatus(500);
    }

    public function test_reset_validates_policy_and_confirmation(): void
    {
        $this->postJson('/api/v1/auth/reset', [
            'token' => 'x', 'email' => 'm@example.com',
            'password' => 'weak', 'password_confirmation' => 'different',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_email_verification_flow(): void
    {
        $user = UserFactory::new()->unverified()->create(['email' => 'new@example.com', 'email_verification_token' => 'abc123']);

        $this->postJson('/api/v1/auth/email/verify', ['token' => 'abc123'])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Email verified successfully.');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->email_verification_token);
        $this->assertDatabaseHas('auth_logs', ['action' => 'email_verified', 'user_id' => $user->id]);
    }

    public function test_email_verification_rejects_unknown_and_expired_tokens(): void
    {
        $this->postJson('/api/v1/auth/email/verify', ['token' => 'nope'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Invalid or expired verification token.');

        $user = UserFactory::new()->unverified()->create(['email_verification_token' => 'old-token']);
        Carbon::setTestNow(Carbon::now()->addHours(25));

        $this->postJson('/api/v1/auth/email/verify', ['token' => 'old-token'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Verification token has expired. Please request a new one.');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_send_verification_email(): void
    {
        $user = UserFactory::new()->unverified()->create(['email' => 'new@example.com']);

        $this->postJson('/api/v1/auth/email/send', ['email' => 'new@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'Verification email sent.');

        Mail::assertSent(VerifyEmail::class, fn ($m) => $m->hasTo('new@example.com'));
        $this->assertNotNull($user->fresh()->email_verification_token);
    }

    public function test_send_verification_email_for_already_verified_user_is_a_noop(): void
    {
        $this->makeUser('member', ['email' => 'm@example.com']);

        $this->postJson('/api/v1/auth/email/send', ['email' => 'm@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'Email already verified.');
        Mail::assertNothingSent();
    }

    public function test_send_verification_email_requires_existing_user(): void
    {
        $this->postJson('/api/v1/auth/email/send', ['email' => 'nobody@example.com'])
            ->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_change_password_requires_authentication_and_policy(): void
    {
        $this->postJson('/api/v1/user/me/changepassword', ['password' => 'NewSecret1!'])->assertStatus(401);

        $user = $this->makeUser('member', ['email' => 'm@example.com']);
        $this->actingAsJwt($user)->postJson('/api/v1/user/me/changepassword', ['password' => 'weak'])
            ->assertStatus(422);

        $this->actingAsJwt($user)->postJson('/api/v1/user/me/changepassword', ['password' => 'NewSecret1!'])
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $this->postJson('/api/v1/auth/login', ['email' => 'm@example.com', 'password' => 'NewSecret1!'])->assertOk();
    }
}
