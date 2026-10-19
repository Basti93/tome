<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HealthAndErrorsTest extends TestCase
{
    public function test_health_is_public_and_reports_ok(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_health_answers_503_without_details_when_the_database_is_down(): void
    {
        DB::partialMock()->shouldReceive('select')->andThrow(new \PDOException('SQLSTATE[HY000] [2002] Connection refused for user root@10.0.0.5'));

        $response = $this->getJson('/api/v1/health')->assertStatus(503)->assertExactJson(['status' => 'unavailable']);

        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertStringNotContainsString('root', $response->getContent());
    }

    public function test_unexpected_exceptions_do_not_leak_their_message_in_production(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/_test/boom', fn () => throw new \RuntimeException('Target class [App\\Secret] does not exist (password=hunter2)'));

        $response = $this->getJson('/api/_test/boom')->assertStatus(500);

        $this->assertSame('Server Error', $response->json('message'));
        $this->assertStringNotContainsString('hunter2', $response->getContent());
        $this->assertStringNotContainsString('Secret', $response->getContent());
    }

    public function test_server_side_http_exceptions_do_not_leak_their_message_in_production(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/_test/http-boom', fn () => throw new HttpException(500, 'internal detail: SELECT * FROM users'));

        $response = $this->getJson('/api/_test/http-boom')->assertStatus(500);

        $this->assertSame('Server Error', $response->json('message'));
        $this->assertStringNotContainsString('SELECT', $response->getContent());
    }

    public function test_missing_models_do_not_reveal_class_names_in_production(): void
    {
        config(['app.debug' => false]);

        $response = $this->actingAsJwt($this->makeUser('trainer'))->getJson('/api/v1/training/999999')->assertNotFound();

        $this->assertSame('Not Found', $response->json('message'));
        $this->assertStringNotContainsString('App\\', $response->getContent());
    }

    public function test_unknown_routes_answer_a_generic_404_in_production(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/v1/does-not-exist')->assertNotFound();

        $this->assertSame('Not Found', $response->json('message'));
    }

    public function test_client_errors_keep_their_messages(): void
    {
        $this->postJson('/api/v1/auth/login', [])->assertStatus(422)->assertJsonStructure(['message', 'errors']);
        $this->getJson('/api/v1/user')->assertStatus(401);
    }

    public function test_validation_failure_without_accept_header_answers_422_json_not_a_redirect(): void
    {
        $trainer = $this->makeUser('trainer');

        // exactly what a fetch() without `Accept: application/json` sends
        $response = $this->call('POST', '/api/v1/trainingSeries', [], [], [], [
            'HTTP_ACCEPT' => '*/*',
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jwtFor($trainer),
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['startTime' => '18:00:00']));

        $response->assertStatus(422)->assertJsonValidationErrors(['endTime', 'weekdays']);
    }

    public function test_unauthenticated_api_request_without_accept_header_answers_401_json(): void
    {
        $response = $this->call('POST', '/api/v1/trainingSeries', [], [], [], ['HTTP_ACCEPT' => 'text/html']);

        $response->assertStatus(401)->assertJsonStructure(['message']);
    }
}
