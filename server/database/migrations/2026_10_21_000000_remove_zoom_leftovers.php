<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Zoom integration is gone: drop trainings.zoom_id and the "Online" location that was only used for it.
 * trainings.location_id and training_series.location_id are nullable with ON DELETE SET NULL, so trainings and
 * series that pointed to the location are kept and just lose their location. Only counts are printed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('locations')->whereRaw('LOWER(TRIM(name)) = ?', ['online'])->pluck('id');

        if ($ids->isNotEmpty()) {
            $trainings = DB::table('trainings')->whereIn('location_id', $ids)->update(['location_id' => null]);
            $series = DB::table('training_series')->whereIn('location_id', $ids)->update(['location_id' => null]);
            DB::table('locations')->whereIn('id', $ids)->delete();
            echo "  removed {$ids->count()} location(s), detached {$trainings} training(s) and {$series} series\n";
        }

        if (Schema::hasColumn('trainings', 'zoom_id')) {
            Schema::table('trainings', fn (Blueprint $table) => $table->dropColumn('zoom_id'));
        }
    }

    public function down(): void
    {
        // the zoom ids and the deleted location rows cannot be restored
        if (! Schema::hasColumn('trainings', 'zoom_id')) {
            Schema::table('trainings', fn (Blueprint $table) => $table->string('zoom_id')->nullable());
        }
    }
};
