<?php

namespace App\Functional\Api\V1\Controllers;

use App\TestCase;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LoginControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $user = new User([
            'firstName' => 'Test',
            'familyName' => 'User',
            'email' => 'test@email.com',
            'password' => '123456',
        ]);

        $user->save();
    }

    public function test_login_successfully()
    {
        $response = $this->postJson('api/v1/auth/login', [
            'email' => 'test@email.com',
            'password' => '123456',
        ]);

        $response->assertJson([
            'status' => 'ok',
        ])->assertJsonStructure([
            'status',
            'token',
            'user',
            'expiresIn',
        ])->isOk();
    }

    public function test_login_with_returns_wrong_credentials_error()
    {
        $this->post('api/v1/auth/login', [
            'email' => 'unknown@email.com',
            'password' => '123456',
        ])->assertJson([
            'status' => 'error',
            'message' => 'Unauthorized',
        ])->assertStatus(403);
    }

    public function test_account_is_locked_after_multiple_failed_login_attempts()
    {
        $response = null;

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->post('api/v1/auth/login', [
                'email' => 'test@email.com',
                'password' => 'wrong-password',
            ]);
        }

        $response->assertJson([
            'status' => 'error',
            'message' => 'Account locked due to too many failed login attempts. Please try again later.',
        ])->assertStatus(403);

        $user = User::where('email', 'test@email.com')->first();

        $this->assertNotNull($user->locked_until);
        $this->assertTrue(Carbon::parse($user->locked_until)->isFuture());
    }

    public function test_locked_account_unlocks_after_lockout_period()
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('api/v1/auth/login', [
                'email' => 'test@email.com',
                'password' => 'wrong-password',
            ]);
        }

        Carbon::setTestNow(Carbon::now()->addMinutes(16));

        $this->post('api/v1/auth/login', [
            'email' => 'test@email.com',
            'password' => '123456',
        ])->assertJson([
            'status' => 'ok',
        ])->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_login_with_returns_validation_error()
    {
        $this->postJson('api/v1/auth/login', [
            'email' => 'test@email.com',
        ])->assertStatus(422);
    }
}
