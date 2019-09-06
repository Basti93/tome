<?php

use Illuminate\Database\Seeder;
use App\Branch;

class BranchesV2TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Branch::where('name', 'Gerätturnen männlich')
            ->update(['short_name' => 'GT-M']);

        Branch::where('name', 'Gerätturnen weiblich')
            ->update(['short_name' => 'GT-W']);

    }
}
