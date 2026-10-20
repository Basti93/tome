<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The reminder before a training for trainers (trainer_upcoming) is opt-in now. Nobody chose it
 * deliberately: it was off in the old system and only switched on by the upgrade, and the profile
 * form stores every key when saved. So it is switched off for everybody who has stored preferences
 * (users without stored preferences get the new default, off).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNotNull('notification_preferences')->orderBy('id')->each(function ($row) {
            $preferences = json_decode($row->notification_preferences, true);
            if (! is_array($preferences)) {
                return;
            }
            $preferences['trainer_upcoming'] = false;
            DB::table('users')->where('id', $row->id)->update(['notification_preferences' => json_encode($preferences)]);
        });
    }

    public function down(): void
    {
        // data migration, nothing to undo
    }
};
