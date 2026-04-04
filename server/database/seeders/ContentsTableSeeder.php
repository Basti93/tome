<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Content;
use App\Branch;

class ContentsTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    Content::firstOrCreate([
      'name' => 'Sprung',
      'branch_id' => Branch::whereName('Gerätturnen weiblich')->first()->id,
      'order' => 1
    ]);
    Content::firstOrCreate([
      'name' => 'Stufenbarren',
      'branch_id' => Branch::whereName('Gerätturnen weiblich')->first()->id,
      'order' => 2
    ]);
    Content::firstOrCreate([
      'name' => 'Schwebebalken',
      'branch_id' => Branch::whereName('Gerätturnen weiblich')->first()->id,
      'order' => 3
    ]);
    Content::firstOrCreate([
      'name' => 'Boden',
      'branch_id' => Branch::whereName('Gerätturnen weiblich')->first()->id,
      'order' => 4
    ]);
    Content::firstOrCreate([
      'name' => 'Krafttraining',
      'branch_id' => Branch::whereName('Gerätturnen weiblich')->first()->id,
      'order' => 5
    ]);
    Content::firstOrCreate([
      'name' => 'Dehnen',
      'branch_id' => Branch::whereName('Gerätturnen weiblich')->first()->id,
      'order' => 6
    ]);
  }
}
