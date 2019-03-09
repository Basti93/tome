<?php

use Illuminate\Database\Seeder;
use App\Branch;
use App\Group;

class BranchesAndGroupsTableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $branch = Branch::firstOrCreate(['name' => 'Gerätturnen männlich']);
    Group::firstOrCreate(['name' => 'Wettkampfturner I', 'branch_id' => $branch->id, 'colorHex' => '#1565c0']);
    Group::firstOrCreate(['name' => 'Wettkampfturner II', 'branch_id' => $branch->id, 'colorHex' => '#64b5f6']);
    Group::firstOrCreate(['name' => 'Wettkampfturner III', 'branch_id' => $branch->id, 'colorHex' => '#de9b61']);



    $branch = Branch::firstOrCreate(['name' => 'Gerätturnen weiblich']);
    Group::firstOrCreate(['name' => 'Wettkampfturner I', 'branch_id' => $branch->id, 'colorHex' => '#33691e']);
    Group::firstOrCreate(['name' => 'Wettkampfturner II', 'branch_id' => $branch->id, 'colorHex' => '#e040fb']);

  }
}
