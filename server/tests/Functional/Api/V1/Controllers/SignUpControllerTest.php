<?php

namespace App\Functional\Api\V1\Controllers;

use App\TestCase;
use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SignUpControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function test_sign_up_successfully()
    {
        $this->post('api/v1/auth/signup', [
            'name' => 'Test User',
            'email' => 'test@email.com',
            'password' => '123456',
        ])->assertJson([
            'status' => 'ok',
        ])->assertStatus(201);
    }

    public function test_sign_up_successfully_with_token_release()
    {
        Config::set('boilerplate.sign_up.release_token', true);

        $this->post('api/v1/auth/signup', [
            'name' => 'Test User',
            'email' => 'test@email.com',
            'password' => '123456',
        ])->assertJsonStructure([
            'status', 'token',
        ])->assertJson([
            'status' => 'ok',
        ])->assertStatus(201);
    }

    public function test_sign_up_returns_validation_error()
    {
        $this->post('api/v1/auth/signup', [
            'name' => 'Test User',
            'email' => 'test@email.com',
        ])->assertJsonStructure([
            'error',
        ])->assertStatus(422);
    }
}
