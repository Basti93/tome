<?php

namespace App\Functional\Api\V1\Controllers;

use Carbon\Carbon;
use Hash;
use App\User;
use App\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LoginControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $user = new User([
            'firstName' => 'Test',
            'familyName' => 'User',
            'email' => 'test@email.com',
            'password' => '123456'
        ]);

        $user->save();
    }

    public function testLoginSuccessfully()
    {
        $response = $this->postJson('api/v1/auth/login', [
            'email' => 'test@email.com',
            'password' => '123456'
        ]);

        $response->assertJson([
            'status' => 'ok'
        ])->assertJsonStructure([
            'status',
            'token',
            'user',
            'expiresIn'
        ])->isOk();
    }

    public function testLoginWithReturnsWrongCredentialsError()
    {
        $this->post('api/v1/auth/login', [
            'email' => 'unknown@email.com',
            'password' => '123456'
        ])->assertJson([
            'status' => 'error',
            'message' => 'Unauthorized'
        ])->assertStatus(403);
    }

    public function testAccountIsLockedAfterMultipleFailedLoginAttempts()
    {
        $response = null;

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->post('api/v1/auth/login', [
                'email' => 'test@email.com',
                'password' => 'wrong-password'
            ]);
        }

        $response->assertJson([
            'status' => 'error',
            'message' => 'Account locked due to too many failed login attempts. Please try again later.'
        ])->assertStatus(403);

        $user = User::where('email', 'test@email.com')->first();

        $this->assertNotNull($user->locked_until);
        $this->assertTrue(Carbon::parse($user->locked_until)->isFuture());
    }

    public function testLockedAccountUnlocksAfterLockoutPeriod()
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('api/v1/auth/login', [
                'email' => 'test@email.com',
                'password' => 'wrong-password'
            ]);
        }

        Carbon::setTestNow(Carbon::now()->addMinutes(16));

        $this->post('api/v1/auth/login', [
            'email' => 'test@email.com',
            'password' => '123456'
        ])->assertJson([
            'status' => 'ok'
        ])->assertStatus(200);

        Carbon::setTestNow();
    }

    public function testLoginWithReturnsValidationError()
    {
        $this->postJson('api/v1/auth/login', [
            'email' => 'test@email.com'
        ])->assertStatus(422);
    }
}
