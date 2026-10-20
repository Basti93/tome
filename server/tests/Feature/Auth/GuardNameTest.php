<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The existing roles/permissions rows have guard_name `api` (JWT era). With Sanctum the `api` guard is the session
 * guard, so no data migration is needed: roles, permissions and Gate::before keep working.
 */
class GuardNameTest extends TestCase
{
    public function test_roles_and_permissions_live_under_the_guard_api(): void
    {
        $this->assertSame(['api'], DB::table('roles')->distinct()->pluck('guard_name')->all());
        $this->assertSame(['api'], DB::table('permissions')->distinct()->pluck('guard_name')->all());
        $this->assertSame('api', config('auth.defaults.guard'));
        $this->assertSame('session', config('auth.guards.api.driver'));
    }

    public function test_permissions_and_the_admin_bypass_work_with_the_session_guard(): void
    {
        $member = $this->makeUser('member');
        $trainer = $this->makeUser('trainer');
        $admin = $this->makeUser('admin');

        $this->assertFalse($member->can('create-training'));
        $this->assertTrue($trainer->can('create-training'));
        $this->assertTrue($admin->can('create-training'), 'Gate::before lets the admin pass');
        $this->assertTrue($admin->hasRole('admin'));
    }
}
