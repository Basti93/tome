<?php

namespace Database\Seeders;

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
         $this->call(BranchesV2TableSeeder::class);
         $this->call(ContentsV2TableSeeder::class);
         $this->call(LocationsTableSeeder::class);
         $this->call(UsersTableSeeder::class);
         $this->call(TrainingSeriesTableSeeder::class);
         $this->call(TrainingsTableSeeder::class);
    }
}
