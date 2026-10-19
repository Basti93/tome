<?php

namespace App\Functional\Api\V1\Controllers;

use App\TestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ForgotPasswordControllerTest extends TestCase
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

    public function test_forgot_password_recovery_successfully()
    {
        $this->post('api/v1/auth/recovery', [
            'email' => 'test@email.com',
        ])->assertJson([
            'status' => 'ok',
        ])->isOk();
    }

    public function test_forgot_password_recovery_returns_user_not_found_error()
    {
        $this->post('api/v1/auth/recovery', [
            'email' => 'unknown@email.com',
        ])->assertJsonStructure([
            'error',
        ])->assertStatus(404);
    }

    public function test_forgot_password_recovery_returns_validation_errors()
    {
        $this->post('api/v1/auth/recovery', [
            'email' => 'i am not an email',
        ])->assertJsonStructure([
            'error',
        ])->assertStatus(422);
    }
}
