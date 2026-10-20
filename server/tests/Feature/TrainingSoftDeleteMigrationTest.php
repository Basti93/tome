<?php

namespace Tests\Feature;

use Database\Factories\TrainingFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrainingSoftDeleteMigrationTest extends TestCase
{
    public function test_flagged_trainings_keep_their_state_and_the_migration_is_reversible(): void
    {
        $live = TrainingFactory::new()->create();
        $flagged = TrainingFactory::new()->create();
        $migration = require database_path('migrations/2026_10_21_000001_trainings_deleted_flag_to_soft_deletes.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('trainings', 'deleted_at'));
        DB::table('trainings')->where('id', $flagged->id)->update(['deleted' => 1, 'updated_at' => '2030-01-02 03:04:05']);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('trainings', 'deleted'));
        $this->assertNull(DB::table('trainings')->where('id', $live->id)->value('deleted_at'));
        $this->assertSame('2030-01-02 03:04:05', DB::table('trainings')->where('id', $flagged->id)->value('deleted_at'));

        $migration->down();
        $this->assertSame([0, 1], [(int) DB::table('trainings')->where('id', $live->id)->value('deleted'), (int) DB::table('trainings')->where('id', $flagged->id)->value('deleted')]);
        $migration->up();
        DB::table('trainings')->delete(); // DDL commits implicitly, so the rows would outlive the test
    }
}
