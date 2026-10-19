<?php

namespace Tests\Feature;

use App\Mail\InvitePassword;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Admins/trainers (permission update-user) invite a user: the user receives the password reset
 * token and link in an invitation mail. Self registration is closed.
 */
class InviteTest extends TestCase
{
    public function test_invite_sends_a_mail_with_a_usable_password_reset_link(): void
    {
        $invitee = $this->makeUser('member', ['email' => 'kind@example.com', 'firstName' => 'Kim', 'email_verified_at' => null]);

        $this->actingAsJwt($this->makeUser('admin'))->postJson("/api/v1/user/{$invitee->id}/invite")
            ->assertOk()->assertExactJson(['status' => 'ok']);

        Mail::assertSent(InvitePassword::class, function (InvitePassword $mail) {
            return $mail->hasTo('kind@example.com')
                && $mail->hasSubject('Einladung zu T.O.M.E. - Passwort festlegen');
        });
        Mail::assertSent(InvitePassword::class, 1);

        // the link has exactly the shape of the password recovery mail (hash router)
        $link = null;
        Mail::assertSent(InvitePassword::class, function (InvitePassword $mail) use (&$link) {
            $link = $mail->resetLink;

            return true;
        });
        $this->assertStringStartsWith(config('app.vue_url').'/#/reset-password?token=', $link);
        parse_str(substr($link, strpos($link, '?') + 1), $query);
        $this->assertSame('kind@example.com', $query['email']);
        $this->assertNotEmpty($query['token']);

        // ... and the token really lets the user set a password and log in
        $this->postJson('/api/v1/auth/reset', [
            'email' => 'kind@example.com',
            'token' => $query['token'],
            'password' => 'Neu12345!x',
            'password_confirmation' => 'Neu12345!x',
        ])->assertOk();
        $this->assertNotNull($invitee->fresh()->email_verified_at, 'setting a password counts as verifying the email');
        $this->postJson('/api/v1/auth/login', ['email' => 'kind@example.com', 'password' => 'Neu12345!x'])->assertOk();
    }

    public function test_invite_mail_is_german_and_names_the_user(): void
    {
        $invitee = $this->makeUser('member', ['email' => 'kind@example.com', 'firstName' => 'Kim']);

        $this->actingAsJwt($this->makeUser('trainer'))->postJson("/api/v1/user/{$invitee->id}/invite")->assertOk();

        Mail::assertSent(InvitePassword::class, function (InvitePassword $mail) {
            $html = $mail->render();

            return str_contains($html, 'Hallo Kim') && str_contains($html, 'Passwort festzulegen') && str_contains($html, e($mail->resetLink));
        });
    }

    public function test_invite_requires_an_email_address(): void
    {
        $invitee = $this->makeUser('member', ['email' => null]);

        $this->actingAsJwt($this->makeUser('admin'))->postJson("/api/v1/user/{$invitee->id}/invite")
            ->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_invite_needs_the_update_user_permission_and_a_login(): void
    {
        $invitee = $this->makeUser('member', ['email' => 'kind@example.com']);

        $this->postJson("/api/v1/user/{$invitee->id}/invite")->assertStatus(401);
        $this->actingAsJwt($this->makeUser('member'))->postJson("/api/v1/user/{$invitee->id}/invite")->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_invite_for_unknown_user_is_404(): void
    {
        $this->actingAsJwt($this->makeUser('admin'))->postJson('/api/v1/user/999999/invite')->assertNotFound();
    }

    public function test_self_registration_is_closed(): void
    {
        $this->postJson('/api/v1/auth/signup', [
            'firstName' => 'Erika', 'familyName' => 'Mustermann', 'email' => 'erika@example.com', 'password' => 'Secret123!',
        ])->assertStatus(404);

        $this->assertDatabaseMissing('users', ['email' => 'erika@example.com']);
    }
}
