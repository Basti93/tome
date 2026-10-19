<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmail;
use App\User;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SignUpTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'firstName' => 'Erika',
            'familyName' => 'Mustermann',
            'email' => 'erika@example.com',
            'password' => 'Secret123!',
        ], $overrides);
    }

    public function test_sign_up_creates_an_unverified_user_and_sends_verification_mail(): void
    {
        $this->postJson('/api/v1/auth/signup', $this->payload())
            ->assertStatus(201)
            ->assertExactJson([
                'status' => 'ok',
                'message' => 'Registration successful. Please check your email to verify your account.',
            ]);

        $user = User::where('email', 'erika@example.com')->firstOrFail();
        $this->assertSame('Erika', $user->firstName);
        $this->assertNull($user->email_verified_at);
        $this->assertNotEmpty($user->email_verification_token);
        $this->assertNotSame('Secret123!', $user->password, 'password must be hashed');
        $this->assertSame(1, (int) $user->active);
        $this->assertCount(0, $user->roles, 'self registered users get no role');

        Mail::assertSent(VerifyEmail::class, fn ($mail) => $mail->hasTo('erika@example.com'));
        $this->assertDatabaseHas('auth_logs', ['action' => 'signup']);
    }

    public function test_sign_up_does_not_log_the_user_in(): void
    {
        $response = $this->postJson('/api/v1/auth/signup', $this->payload());

        $response->assertCookieMissing('jwt_token');
        $response->assertJsonMissingPath('token');
    }

    public function test_sign_up_requires_all_fields_with_german_messages(): void
    {
        $this->postJson('/api/v1/auth/signup', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['firstName', 'familyName', 'email', 'password'])
            ->assertJsonPath('errors.firstName.0', 'Der Vorname ist erforderlich.')
            ->assertJsonPath('errors.email.0', 'Die E-Mail ist erforderlich.');
    }

    #[DataProvider('weakPasswords')]
    public function test_sign_up_enforces_the_password_policy(string $weak): void
    {
        $this->postJson('/api/v1/auth/signup', $this->payload(['password' => $weak]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }

    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Sh0rt!'],
            'no uppercase' => ['alllowercase1!'],
            'no lowercase' => ['ALLUPPERCASE1!'],
            'no digit' => ['NoDigitsHere!'],
            'no special character' => ['NoSpecial123a'],
        ];
    }

    public function test_sign_up_rejects_duplicate_email(): void
    {
        $this->makeUser(null, ['email' => 'erika@example.com']);

        $this->postJson('/api/v1/auth/signup', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Diese E-Mail ist bereits registriert.');
    }

    public function test_sign_up_is_throttled_to_three_requests_per_minute(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/signup', $this->payload(['email' => "user$i@example.com"]))
                ->assertStatus(201);
        }

        $this->postJson('/api/v1/auth/signup', $this->payload(['email' => 'user4@example.com']))
            ->assertStatus(429);
    }
}
