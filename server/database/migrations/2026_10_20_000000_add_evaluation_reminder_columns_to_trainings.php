<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->unsignedTinyInteger('evaluation_reminder_count')->nullable();
            $table->dateTime('evaluation_reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn(['evaluation_reminder_count', 'evaluation_reminder_sent_at']);
        });
    }
};
