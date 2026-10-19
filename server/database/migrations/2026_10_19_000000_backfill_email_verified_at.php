<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Login requires email_verified_at, but the column was added as NULL. Without this backfill every
 * account that existed before the column (all production users) would be locked out.
 * Other columns added later need no backfill: failed_login_attempts defaults to 0, locked_until and
 * notification_preferences (defaults applied in code) are legitimately NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->whereNotNull('email')
            ->whereNotNull('password')
            ->update(['email_verified_at' => DB::raw('COALESCE(created_at, NOW())')]);
    }

    public function down(): void
    {
        // data migration, nothing to undo
    }
};
