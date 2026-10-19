<?php

namespace App\Functional\Api\V1\Controllers;

use App\TestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UserControllerTest extends TestCase
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

    public function test_me()
    {
        $response = $this->post('api/v1/auth/login', [
            'email' => 'test@email.com',
            'password' => '123456',
        ]);

        $response->assertStatus(200);

        $responseJSON = json_decode($response->getContent(), true);
        $token = $responseJSON['token'];

        $this->get('api/v1/auth/me?token='.$token, [], [])->assertJson([
            'name' => 'Test',
            'email' => 'test@email.com',
        ])->isOk();
    }
}
