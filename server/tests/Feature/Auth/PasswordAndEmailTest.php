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

    public function test_recovery_for_unknown_email_looks_exactly_like_success(): void
    {
        $this->postJson('/api/v1/auth/recovery', ['email' => 'nobody@example.com'])->assertOk()->assertExactJson(['status' => 'ok']);
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
            $this->postJson('/api/v1/auth/recovery', ['email' => 'nobody@example.com'])->assertOk();
        }
        $this->postJson('/api/v1/auth/recovery', ['email' => 'nobody@example.com'])->assertStatus(429)
            ->assertJsonPath('message', 'Zu viele Anfragen. Bitte warte einen Moment und versuche es dann erneut.')
            ->assertHeader('Retry-After');
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

    public function test_reset_with_an_invalid_token_is_a_german_422(): void
    {
        $this->makeUser('member', ['email' => 'm@example.com']);

        $this->postJson('/api/v1/auth/reset', [
            'token' => 'invalid',
            'email' => 'm@example.com',
            'password' => 'NewSecret1!',
            'password_confirmation' => 'NewSecret1!',
        ])->assertStatus(422)->assertJsonPath('code', 'invalid_token')
            ->assertJsonPath('message', 'Der Link ist ungültig oder abgelaufen. Bitte fordere über „Passwort vergessen“ einen neuen an.');

        // unknown e-mail: same answer, no account enumeration
        $this->postJson('/api/v1/auth/reset', [
            'token' => 'invalid', 'email' => 'nobody@example.com', 'password' => 'NewSecret1!', 'password_confirmation' => 'NewSecret1!',
        ])->assertStatus(422)->assertJsonPath('code', 'invalid_token');
    }

    public function test_an_expired_or_used_reset_token_is_rejected(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);
        $body = fn ($token) => ['token' => $token, 'email' => 'm@example.com', 'password' => 'NewSecret1!', 'password_confirmation' => 'NewSecret1!'];

        $expired = Password::createToken($user);
        $this->travel(61)->minutes();
        $this->postJson('/api/v1/auth/reset', $body($expired))->assertStatus(422)->assertJsonPath('code', 'invalid_token');
        $this->travelBack();

        $used = Password::createToken($user);
        $this->postJson('/api/v1/auth/reset', $body($used))->assertOk();
        $this->postJson('/api/v1/auth/reset', $body($used))->assertStatus(422);
    }

    public function test_a_reset_lifts_the_lockout_and_marks_an_invited_user_registered(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com', 'registered' => 0, 'failed_login_attempts' => 5, 'locked_until' => now()->addMinutes(10)]);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset', ['token' => $token, 'email' => 'm@example.com', 'password' => 'NewSecret1!', 'password_confirmation' => 'NewSecret1!'])->assertOk();

        $fresh = $user->fresh();
        $this->assertNull($fresh->locked_until);
        $this->assertSame(0, (int) $fresh->failed_login_attempts);
        $this->assertSame(1, (int) $fresh->registered);
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
            ->assertJsonPath('message', 'Deine E-Mail-Adresse wurde bestätigt. Du kannst dich jetzt anmelden.');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->email_verification_token);
        $this->assertDatabaseHas('auth_logs', ['action' => 'email_verified', 'user_id' => $user->id]);
    }

    public function test_email_verification_rejects_unknown_and_expired_tokens(): void
    {
        $this->postJson('/api/v1/auth/email/verify', ['token' => 'nope'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Der Bestätigungslink ist ungültig oder abgelaufen. Bitte fordere auf der Anmeldeseite einen neuen an.');

        $user = UserFactory::new()->unverified()->create(['email_verification_token' => 'old-token']);
        Carbon::setTestNow(Carbon::now()->addHours(25));

        $this->postJson('/api/v1/auth/email/verify', ['token' => 'old-token'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Der Bestätigungslink ist ungültig oder abgelaufen. Bitte fordere auf der Anmeldeseite einen neuen an.');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_send_verification_email(): void
    {
        $user = UserFactory::new()->unverified()->create(['email' => 'new@example.com']);

        $this->postJson('/api/v1/auth/email/send', ['email' => 'new@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'Falls ein unbestätigtes Konto mit dieser E-Mail-Adresse existiert, wurde eine E-Mail gesendet.');

        Mail::assertSent(VerifyEmail::class, fn ($m) => $m->hasTo('new@example.com'));
        $this->assertNotNull($user->fresh()->email_verification_token);
    }

    public function test_send_verification_email_for_already_verified_user_is_a_noop(): void
    {
        $this->makeUser('member', ['email' => 'm@example.com']);

        $this->postJson('/api/v1/auth/email/send', ['email' => 'm@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'Falls ein unbestätigtes Konto mit dieser E-Mail-Adresse existiert, wurde eine E-Mail gesendet.');
        Mail::assertNothingSent();
    }

    public function test_send_verification_email_for_an_unknown_address_looks_the_same_and_sends_nothing(): void
    {
        $this->postJson('/api/v1/auth/email/send', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'Falls ein unbestätigtes Konto mit dieser E-Mail-Adresse existiert, wurde eine E-Mail gesendet.');
        Mail::assertNothingSent();
        $this->postJson('/api/v1/auth/email/send', ['email' => 'kaputt'])->assertStatus(422);
    }

    public function test_change_password_requires_authentication_the_current_password_and_the_policy(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);
        $this->actingAsUser($user)->postJson('/api/v1/user/me/changepassword', ['current_password' => 'Password1!', 'password' => 'weak'])
            ->assertStatus(422);

        $this->actingAsUser($user)->postJson('/api/v1/user/me/changepassword', ['password' => 'NewSecret1!'])
            ->assertStatus(422)->assertJsonValidationErrors(['currentPassword']);

        $this->actingAsUser($user)->postJson('/api/v1/user/me/changepassword', ['current_password' => 'Falsch123!x', 'password' => 'NewSecret1!'])
            ->assertStatus(422)->assertJsonPath('message', 'Das aktuelle Passwort ist falsch.');
        $this->postJson('/api/v1/auth/login', ['email' => 'm@example.com', 'password' => 'NewSecret1!'])->assertStatus(403);

        $this->actingAsUser($user)->postJson('/api/v1/user/me/changepassword', ['current_password' => 'Password1!', 'password' => 'NewSecret1!'])
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $this->postJson('/api/v1/auth/login', ['email' => 'm@example.com', 'password' => 'NewSecret1!'])->assertOk();
    }

    public function test_change_password_requires_a_login(): void
    {
        $this->postJson('/api/v1/user/me/changepassword', ['current_password' => 'Password1!', 'password' => 'NewSecret1!'])->assertStatus(401);
    }

    public function test_guessing_the_current_password_is_throttled(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);
        for ($i = 0; $i < 5; $i++) {
            $this->actingAsUser($user)->postJson('/api/v1/user/me/changepassword', ['current_password' => 'Falsch123!x', 'password' => 'NewSecret1!'])->assertStatus(422);
        }

        $this->actingAsUser($user)->postJson('/api/v1/user/me/changepassword', ['current_password' => 'Password1!', 'password' => 'NewSecret1!'])
            ->assertStatus(429);
    }

    public function test_the_change_password_limit_is_per_user_and_not_eaten_by_logins_of_the_same_ip(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'other@example.com', 'password' => 'Falsch123!x']);
        }

        $this->actingAsUser($user)->postJson('/api/v1/user/me/changepassword', ['current_password' => 'Password1!', 'password' => 'NewSecret1!'])
            ->assertStatus(201);
    }
}
