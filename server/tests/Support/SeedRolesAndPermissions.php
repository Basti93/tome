<?php

namespace Tests\Support;

use Database\Seeders\PermissionsTableSeeder;
use Database\Seeders\RolesTableSeeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds exactly the roles and permissions that production uses (the real seeders),
 * and nothing else: admin (everything via Gate::before), trainer and member.
 */
class SeedRolesAndPermissions
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        (new PermissionsTableSeeder)->run();
        (new RolesTableSeeder)->run();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
