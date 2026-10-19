<?php

namespace Tests\Feature;

use Database\Factories\LocationFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\TrainingSeriesFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RemoveOnlineLocationTest extends TestCase
{
    public function test_online_locations_are_deleted_and_references_are_detached_but_kept(): void
    {
        $online = LocationFactory::new()->create(['name' => '  ONLINE ']);
        $hall = LocationFactory::new()->create(['name' => 'Sporthalle']);
        $onlineTraining = TrainingFactory::new()->create(['location_id' => $online->id]);
        $hallTraining = TrainingFactory::new()->create(['location_id' => $hall->id]);
        $onlineSeries = TrainingSeriesFactory::new()->create(['location_id' => $online->id]);

        ob_start();
        (require database_path('migrations/2026_10_21_000000_remove_zoom_leftovers.php'))->up();
        $output = ob_get_clean();

        $this->assertStringContainsString('removed 1 location(s), detached 1 training(s) and 1 series', $output);
        $this->assertDatabaseMissing('locations', ['id' => $online->id]);
        $this->assertDatabaseHas('locations', ['id' => $hall->id]);
        $this->assertNull(DB::table('trainings')->where('id', $onlineTraining->id)->value('location_id'));
        $this->assertSame($hall->id, (int) DB::table('trainings')->where('id', $hallTraining->id)->value('location_id'));
        $this->assertNull(DB::table('training_series')->where('id', $onlineSeries->id)->value('location_id'));
        $this->assertFalse(Schema::hasColumn('trainings', 'zoom_id'));
    }
}
