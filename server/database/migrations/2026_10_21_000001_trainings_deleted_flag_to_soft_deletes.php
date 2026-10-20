<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * trainings.deleted (boolean) becomes trainings.deleted_at (Eloquent SoftDeletes).
 * Flagged rows keep their state: deleted_at = updated_at (or now if that is empty).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', fn (Blueprint $table) => $table->softDeletes());

        DB::table('trainings')->where('deleted', 1)->update(['deleted_at' => DB::raw('COALESCE(updated_at, NOW())')]);

        Schema::table('trainings', fn (Blueprint $table) => $table->dropColumn('deleted'));
    }

    public function down(): void
    {
        Schema::table('trainings', fn (Blueprint $table) => $table->boolean('deleted')->default(0));

        DB::table('trainings')->whereNotNull('deleted_at')->update(['deleted' => 1]);

        Schema::table('trainings', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
