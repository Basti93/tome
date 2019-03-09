<?php

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
    Location::firstOrCreate(['name' => 'Gymnasium Landau']);
    Location::firstOrCreate(['name' => 'Dreifachturnhalle Landau']);
    Location::firstOrCreate(['name' => 'Auswärts']);
    Location::firstOrCreate(['name' => 'Realschule Landau']);
    Location::firstOrCreate(['name' => 'Grundschule Landau']);

  }
}
