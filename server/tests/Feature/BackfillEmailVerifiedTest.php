<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillEmailVerifiedTest extends TestCase
{
    private function migration(): object
    {
        return require database_path('migrations/2026_10_19_000000_backfill_email_verified_at.php');
    }

    public function test_existing_accounts_with_email_and_password_become_verified_and_can_log_in(): void
    {
        $legacy = $this->makeUser('trainer', ['email' => 'alt@example.com', 'email_verified_at' => null]);
        DB::table('users')->where('id', $legacy->id)->update(['created_at' => '2020-03-04 10:00:00']);
        $child = $this->makeUser(null, ['email' => null, 'password' => null, 'registered' => 0, 'email_verified_at' => null]);
        $already = $this->makeUser('member', ['email_verified_at' => '2024-01-01 00:00:00']);
        $this->postJson('/api/v1/auth/login', ['email' => 'alt@example.com', 'password' => 'Password1!'])->assertStatus(403);

        $this->migration()->up();

        $this->assertSame('2020-03-04 10:00:00', $legacy->fresh()->email_verified_at->format('Y-m-d H:i:s'));
        $this->assertNull($child->fresh()->email_verified_at, 'accounts without email/password stay unverified');
        $this->assertSame('2024-01-01 00:00:00', $already->fresh()->email_verified_at->format('Y-m-d H:i:s'));
        $this->postJson('/api/v1/auth/login', ['email' => 'alt@example.com', 'password' => 'Password1!'])->assertOk();
    }

    public function test_users_without_created_at_get_the_current_time(): void
    {
        $user = $this->makeUser('member', ['email_verified_at' => null]);
        DB::table('users')->where('id', $user->id)->update(['created_at' => null]);

        $this->migration()->up();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
