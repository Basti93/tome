<?php

use App\Branch;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainerBranchTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('trainer_branch')->insert([
            'user_id' => User::whereEmail('bindersebastian@online.de')->first()->id,
            'branch_id' => Branch::whereName('Gerätturnen männlich')->first()->id,
        ]);
        DB::table('trainer_branch')->insert([
            'user_id' => User::whereEmail('davidbauer1609@gmail.com')->first()->id,
            'branch_id' => Branch::whereName('Gerätturnen männlich')->first()->id,
        ]);
        DB::table('trainer_branch')->insert([
            'user_id' => User::whereEmail('julian.binder@online.de')->first()->id,
            'branch_id' => Branch::whereName('Gerätturnen männlich')->first()->id,
        ]);
        DB::table('trainer_branch')->insert([
            'user_id' => User::whereEmail('georgmalerwein@web.de')->first()->id,
            'branch_id' => Branch::whereName('Gerätturnen männlich')->first()->id,
        ]);
        DB::table('trainer_branch')->insert([
            'user_id' => User::whereEmail('michael.kindlein@outlook.com')->first()->id,
            'branch_id' => Branch::whereName('Gerätturnen männlich')->first()->id,
        ]);
        DB::table('trainer_branch')->insert([
            'user_id' => User::whereEmail('maximilian.kindlein@googlemail.com')->first()->id,
            'branch_id' => Branch::whereName('Gerätturnen männlich')->first()->id,
        ]);
        DB::table('trainer_branch')->insert([
            'user_id' => User::whereEmail('hinkel993@web.de')->first()->id,
            'branch_id' => Branch::whereName('Gerätturnen männlich')->first()->id,
        ]);
        DB::table('trainer_branch')->insert([
            'user_id' => User::whereEmail('alandes@gmx.de')->first()->id,
            'branch_id' => Branch::whereName('Gerätturnen männlich')->first()->id,
        ]);

    }
}
