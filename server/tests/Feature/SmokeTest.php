<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_health_route_and_roles_are_seeded(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertExactJson(['status' => 'ok']);
        $this->assertDatabaseHas('roles', ['name' => 'trainer', 'guard_name' => 'api']);
    }
}
