<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Location;

class LocationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Location::firstOrCreate(['name' => 'Main Hall']);
        Location::firstOrCreate(['name' => 'Gym A']);
        Location::firstOrCreate(['name' => 'Outdoor Court']);
    }
}
