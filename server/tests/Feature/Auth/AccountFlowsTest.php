<?php

namespace Tests\Feature\Auth;

use App\Mail\InvitePassword;
use App\Mail\ResetPassword;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * The account workflow end to end on the API: create a user, invite, set the password, log in,
 * forgot password, change password. Complements InviteTest, LoginTest and PasswordAndEmailTest.
 */
class AccountFlowsTest extends TestCase
{
    private function tokenFrom(string $link): array
    {
        parse_str(substr($link, strpos($link, '?') + 1), $query);

        return $query;
    }

    private function sentLink(string $mailable, string $to): string
    {
        $link = null;
        Mail::assertSent($mailable, function ($mail) use (&$link, $to) {
            if ($mail->hasTo($to)) {
                $link = $mail->resetLink;
            }

            return $mail->hasTo($to);
        });

        return $link;
    }

    public function test_admin_creates_a_user_with_email_invites_them_and_the_user_logs_in(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsJwt($admin)->postJson('/api/v1/user/unregistered', ['firstName' => 'Kim', 'familyName' => 'Neu', 'email' => 'kim@example.com'])
            ->assertStatus(201);
        $kim = User::where('email', 'kim@example.com')->firstOrFail();
        $this->assertSame(0, (int) $kim->registered);

        // not able to log in before the invitation was accepted
        $this->postJson('/api/v1/auth/login', ['email' => 'kim@example.com', 'password' => 'Egal1234!'])->assertStatus(403);

        $this->actingAsJwt($admin)->postJson("/api/v1/user/{$kim->id}/invite")->assertOk();
        $query = $this->tokenFrom($this->sentLink(InvitePassword::class, 'kim@example.com'));

        $this->postJson('/api/v1/auth/reset', ['email' => $query['email'], 'token' => $query['token'], 'password' => 'Neu12345!x', 'password_confirmation' => 'Neu12345!x'])->assertOk();

        $this->postJson('/api/v1/auth/login', ['email' => 'kim@example.com', 'password' => 'Neu12345!x'])->assertOk()->assertJsonPath('status', 'ok');
        $this->assertSame(1, (int) $kim->fresh()->registered);
        $this->assertNotNull($kim->fresh()->email_verified_at);
    }

    public function test_the_invite_link_cannot_be_used_twice(): void
    {
        $kim = $this->makeUser('member', ['email' => 'kim@example.com']);
        $this->actingAsJwt($this->makeUser('admin'))->postJson("/api/v1/user/{$kim->id}/invite")->assertOk();
        $query = $this->tokenFrom($this->sentLink(InvitePassword::class, 'kim@example.com'));
        $body = ['email' => $query['email'], 'token' => $query['token'], 'password' => 'Neu12345!x', 'password_confirmation' => 'Neu12345!x'];

        $this->postJson('/api/v1/auth/reset', $body)->assertOk();
        $this->postJson('/api/v1/auth/reset', $body)->assertStatus(422)->assertJsonPath('code', 'invalid_token');
    }

    public function test_an_already_active_user_can_be_invited_again_and_gets_a_working_link(): void
    {
        $active = $this->makeUser('member', ['email' => 'a@example.com']);

        $this->actingAsJwt($this->makeUser('trainer'))->postJson("/api/v1/user/{$active->id}/invite")->assertOk();
        $query = $this->tokenFrom($this->sentLink(InvitePassword::class, 'a@example.com'));

        $this->postJson('/api/v1/auth/reset', ['email' => 'a@example.com', 'token' => $query['token'], 'password' => 'Neu12345!x', 'password_confirmation' => 'Neu12345!x'])->assertOk();
    }

    public function test_inviting_a_user_without_email_is_a_german_422(): void
    {
        $kid = $this->makeUser(null, ['email' => null, 'registered' => 0]);

        $this->actingAsJwt($this->makeUser('admin'))->postJson("/api/v1/user/{$kid->id}/invite")
            ->assertStatus(422)->assertJsonPath('message', 'Der Benutzer hat keine E-Mail-Adresse.');
        Mail::assertNothingSent();
    }

    public function test_emails_must_be_unique_and_empty_means_none(): void
    {
        $admin = $this->makeUser('admin');
        $taken = $this->makeUser('member', ['email' => 'taken@example.com']);
        $kidA = $this->actingAsJwt($admin)->postJson('/api/v1/user/unregistered', ['firstName' => 'A', 'familyName' => 'Kind', 'email' => ''])->assertStatus(201);
        $this->actingAsJwt($admin)->postJson('/api/v1/user/unregistered', ['firstName' => 'B', 'familyName' => 'Kind', 'email' => ''])->assertStatus(201);
        $this->assertSame(2, User::whereNull('email')->where('familyName', 'Kind')->count(), 'several users without e-mail are fine');

        $this->actingAsJwt($admin)->postJson('/api/v1/user/unregistered', ['firstName' => 'C', 'familyName' => 'Kind', 'email' => 'taken@example.com'])
            ->assertStatus(422)->assertJsonPath('errors.email.0', 'Diese E-Mail-Adresse wird bereits verwendet.');

        // editing: another user's address is refused, the own address (unchanged) is fine
        $other = $this->makeUser('member', ['email' => 'other@example.com']);
        $payload = fn ($email) => ['firstName' => 'X', 'familyName' => 'Y', 'email' => $email, 'groupIds' => [], 'trainerBranchIds' => []];
        $this->actingAsJwt($admin)->putJson("/api/v1/user/{$other->id}", $payload('taken@example.com'))->assertStatus(422);
        $this->actingAsJwt($admin)->putJson("/api/v1/user/{$other->id}", $payload('other@example.com'))->assertStatus(201);
        $this->actingAsJwt($admin)->putJson("/api/v1/user/{$other->id}", $payload('neu@example.com'))->assertStatus(201);
        $this->assertSame('neu@example.com', $other->fresh()->email);
        $this->assertSame('taken@example.com', $taken->fresh()->email);
    }

    public function test_a_trainer_cannot_set_another_users_password_or_preferences_through_the_user_update(): void
    {
        $admin = $this->makeUser('admin', ['email' => 'admin@example.com']);
        $this->actingAsJwt($this->makeUser('trainer'))->putJson("/api/v1/user/{$admin->id}", [
            'firstName' => 'Admin', 'familyName' => 'Boss', 'groupIds' => [], 'trainerBranchIds' => [],
            'password' => 'Hacked123!x', 'notification_preferences' => ['admin_messages' => false],
        ])->assertStatus(201);

        $this->postJson('/api/v1/auth/login', ['email' => 'admin@example.com', 'password' => 'Hacked123!x'])->assertStatus(403);
        $this->assertNull($admin->fresh()->notification_preferences);
    }

    public function test_updating_the_own_profile_without_an_email_field_keeps_the_email(): void
    {
        $user = $this->makeUser('member', ['email' => 'me@example.com']);

        $this->actingAsJwt($user)->putJson('/api/v1/user/me', ['firstName' => 'Neu', 'familyName' => 'Name'])->assertStatus(201);

        $this->assertSame('me@example.com', $user->fresh()->email);
    }

    public function test_forgot_password_and_set_a_new_one_then_the_old_one_is_dead(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);
        $this->postJson('/api/v1/auth/recovery', ['email' => 'm@example.com'])->assertOk();
        $query = $this->tokenFrom($this->sentLink(ResetPassword::class, 'm@example.com'));

        $this->postJson('/api/v1/auth/reset', ['email' => 'm@example.com', 'token' => $query['token'], 'password' => 'Neu12345!x', 'password_confirmation' => 'Neu12345!x'])->assertOk();

        $this->postJson('/api/v1/auth/login', ['email' => 'm@example.com', 'password' => 'Neu12345!x'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['email' => 'm@example.com', 'password' => 'Password1!'])->assertStatus(403);
    }

    public function test_reset_link_uses_the_configured_frontend_url_with_a_hash_route(): void
    {
        config(['app.vue_url' => 'https://app.example.org']);
        $this->makeUser('member', ['email' => 'm@example.com']);

        $this->postJson('/api/v1/auth/recovery', ['email' => 'm@example.com'])->assertOk();

        $this->assertStringStartsWith('https://app.example.org/#/reset-password?token=', $this->sentLink(ResetPassword::class, 'm@example.com'));
    }

    public function test_changing_the_password_in_the_profile_replaces_the_old_one(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);

        $this->actingAsJwt($user)->postJson('/api/v1/user/me/changepassword', ['current_password' => 'Password1!', 'password' => 'Neu12345!x'])->assertStatus(201);

        $this->postJson('/api/v1/auth/login', ['email' => 'm@example.com', 'password' => 'Neu12345!x'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['email' => 'm@example.com', 'password' => 'Password1!'])->assertStatus(403);
    }

    public function test_only_admins_may_change_the_email_of_an_account_that_can_log_in(): void
    {
        $victim = $this->makeUser('admin', ['email' => 'boss@example.com']);
        $kid = $this->makeUser(null, ['email' => null, 'registered' => 0]);
        DB::table('users')->where('id', $kid->id)->update(['password' => null]);
        $payload = fn ($email) => ['firstName' => 'X', 'familyName' => 'Y', 'email' => $email, 'groupIds' => [], 'trainerBranchIds' => []];
        $trainer = $this->makeUser('trainer');

        $this->actingAsJwt($trainer)->putJson("/api/v1/user/{$victim->id}", $payload('mine@example.com'))
            ->assertStatus(403)->assertJsonPath('message', 'Die E-Mail-Adresse eines Kontos mit Anmeldung kann nur ein Admin ändern.');
        $this->assertSame('boss@example.com', $victim->fresh()->email);

        $this->actingAsJwt($trainer)->putJson("/api/v1/user/{$kid->id}", $payload('kind@example.com'))->assertStatus(201);
        $this->assertSame('kind@example.com', $kid->fresh()->email);

        $this->actingAsJwt($this->makeUser('admin'))->putJson("/api/v1/user/{$victim->id}", $payload('neu@example.com'))->assertStatus(201);
    }

    public function test_invite_links_are_valid_for_7_days_but_reset_links_only_for_60_minutes(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);
        $admin = $this->makeUser('admin');
        $body = fn ($token) => ['token' => $token, 'email' => 'm@example.com', 'password' => 'Neu12345!x', 'password_confirmation' => 'Neu12345!x'];

        $this->actingAsJwt($admin)->postJson("/api/v1/user/{$user->id}/invite")->assertOk();
        $invite = $this->tokenFrom($this->sentLink(InvitePassword::class, 'm@example.com'))['token'];
        $this->travel(2)->days();
        $this->postJson('/api/v1/auth/reset', $body($invite))->assertOk();

        // a second invitation that is not used for 8 days has expired
        $this->travelBack();
        $this->actingAsJwt($admin)->postJson("/api/v1/user/{$user->id}/invite")->assertOk();
        $expired = null;
        Mail::assertSent(InvitePassword::class, function ($mail) use (&$expired) {
            $expired = $this->tokenFrom($mail->resetLink)['token'];

            return true;
        });
        $this->travel(8)->days();
        $this->postJson('/api/v1/auth/reset', $body($expired))->assertStatus(422)->assertJsonPath('code', 'invalid_token');
        $this->travelBack();

        // a reset token is NOT stretched to 7 days: it is rejected after 61 minutes (and not accepted as an invite)
        $reset = Password::createToken($user);
        $this->travel(61)->minutes();
        $this->postJson('/api/v1/auth/reset', $body($reset))->assertStatus(422);
        $this->travelBack();
        $reset2 = Password::createToken($user);
        $this->travel(30)->minutes();
        $this->postJson('/api/v1/auth/reset', $body($reset2))->assertOk();
    }

    public function test_the_invite_mail_names_the_validity_in_days(): void
    {
        $user = $this->makeUser('member', ['email' => 'm@example.com']);

        $this->actingAsJwt($this->makeUser('admin'))->postJson("/api/v1/user/{$user->id}/invite")->assertOk();

        Mail::assertSent(InvitePassword::class, fn ($mail) => str_contains($mail->render(), '7 Tage gültig'));
    }
}
