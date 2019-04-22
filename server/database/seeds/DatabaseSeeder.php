<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         $this->call(PermissionsTableSeeder::class);
         $this->call(RolesTableSeeder::class);
         $this->call(BranchesAndGroupsTableSeeder::class);
         $this->call(ContentsTableSeeder::class);
         $this->call(LocationsTableSeeder::class);
         $this->call(OldDatabaseMigrationSeeder::class);
         $this->call(TrainingSeriesTableSeeder::class);
    }
}
