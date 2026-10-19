<?php

namespace Database\Seeders;

use App\Location;
use Illuminate\Database\Seeder;

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
