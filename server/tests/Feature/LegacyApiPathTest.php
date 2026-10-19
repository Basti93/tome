<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyApiPathTest extends TestCase
{
    public function test_legacy_double_api_prefix_returns_410_json(): void
    {
        foreach (['get', 'post'] as $method) {
            $this->json($method, '/api/api/training/upcoming')
                ->assertStatus(410)
                ->assertExactJson(['message' => 'Diese App-Version ist veraltet. Bitte Seite neu laden.']);
        }
    }

    public function test_current_api_path_is_not_affected(): void
    {
        $this->getJson('/api/v1/health')->assertOk();
    }
}
