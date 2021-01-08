<?php

use Illuminate\Database\Seeder;
use App\Content;
use App\Branch;

class ContentsV2TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Content::where('name', 'Sprung')
            ->where('branch_id', Branch::whereName('Gerätturnen männlich')->first()->id)
            ->update(['image_name' => 'm_vault.png']);
        Content::where('name', 'Boden')
            ->where('branch_id', Branch::whereName('Gerätturnen männlich')->first()->id)
            ->update(['image_name' => 'm_floor.png']);
        Content::where('name', 'Barren')
            ->where('branch_id', Branch::whereName('Gerätturnen männlich')->first()->id)
            ->update(['image_name' => 'm_parallel_bars.png']);
        Content::where('name', 'Reck')
            ->where('branch_id', Branch::whereName('Gerätturnen männlich')->first()->id)
            ->update(['image_name' => 'm_high_bar.png']);
        Content::where('name', 'Pauschenpferd')
            ->where('branch_id', Branch::whereName('Gerätturnen männlich')->first()->id)
            ->update(['image_name' => 'm_pommelhorse.png']);
        Content::where('name', 'Ringe')
            ->where('branch_id', Branch::whereName('Gerätturnen männlich')->first()->id)
            ->update(['image_name' => 'm_rings.png']);
        Content::where('name', 'Zombieball')
            ->where('branch_id', Branch::whereName('Gerätturnen männlich')->first()->id)
            ->update(['image_name' => 'game.png']);
        Content::where('name', 'Dehnen')
            ->where('branch_id', Branch::whereName('Gerätturnen männlich')->first()->id)
            ->update(['image_name' => 'flexibility.png']);
        Content::where('name', 'Krafttraining')
            ->where('branch_id', Branch::whereName('Gerätturnen männlich')->first()->id)
            ->update(['image_name' => 'strength.png']);


        Content::where('name', 'Schwebebalken')
            ->where('branch_id', Branch::whereName('Gerätturnen weiblich')->first()->id)
            ->update(['image_name' => 'f_beam.png']);
        Content::where('name', 'Boden')
            ->where('branch_id', Branch::whereName('Gerätturnen weiblich')->first()->id)
            ->update(['image_name' => 'f_floor.png']);
        Content::where('name', 'Stufenbarren')
            ->where('branch_id', Branch::whereName('Gerätturnen weiblich')->first()->id)
            ->update(['image_name' => 'f_uneven_bars.png']);
        Content::where('name', 'Sprung')
            ->where('branch_id', Branch::whereName('Gerätturnen weiblich')->first()->id)
            ->update(['image_name' => 'f_vault.png']);
        Content::where('name', 'Sprung')
            ->where('branch_id', Branch::whereName('Gerätturnen weiblich')->first()->id)
            ->update(['image_name' => 'f_vault.png']);
        Content::where('name', 'Dehnen')
            ->where('branch_id', Branch::whereName('Gerätturnen weiblich')->first()->id)
            ->update(['image_name' => 'flexibility.png']);
        Content::where('name', 'Krafttraining')
            ->where('branch_id', Branch::whereName('Gerätturnen weiblich')->first()->id)
            ->update(['image_name' => 'strength.png']);
        Content::firstOrCreate([
            'name' => 'Spielen',
            'branch_id' => Branch::whereName('Gerätturnen weiblich')->first()->id,
            'image_name' => 'game.png',
        ]);
    }
}
