<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\User;
use App\Branch;
use App\Group;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::firstOrNew(['email' => 'admin@tome.local']);
        $admin->firstName = 'Admin';
        $admin->familyName = 'User';
        $admin->password = 'password';
        $admin->active = 1;
        $admin->registered = 1;
        $admin->save();
        $admin->assignRole('admin');

        $trainer = User::firstOrNew(['email' => 'trainer@tome.local']);
        $trainer->firstName = 'Trainer';
        $trainer->familyName = 'One';
        $trainer->password = 'password';
        $trainer->active = 1;
        $trainer->registered = 1;
        $trainer->save();
        $trainer->assignRole('trainer');

        $member = User::firstOrNew(['email' => 'member@tome.local']);
        $member->firstName = 'Member';
        $member->familyName = 'One';
        $member->password = 'password';
        $member->active = 1;
        $member->registered = 1;
        $member->save();
        $member->assignRole('member');

        $support = User::firstOrNew(['email' => 'support@tome.local']);
        $support->firstName = 'Support';
        $support->familyName = 'User';
        $support->password = 'password';
        $support->active = 1;
        $support->registered = 1;
        $support->save();
        $support->assignRole('trainer');

        $group = Group::where('name', 'Wettkampfturner I')->first();
        $femaleBranch = Branch::where('name', 'Gerätturnen weiblich')->first();

        if ($group) {
            $member->groups()->syncWithoutDetaching([$group->id]);
            $trainer->groups()->syncWithoutDetaching([$group->id]);
            $support->groups()->syncWithoutDetaching([$group->id]);
        }

        if ($femaleBranch) {
            $trainer->trainerBranches()->syncWithoutDetaching([$femaleBranch->id]);
            $support->trainerBranches()->syncWithoutDetaching([$femaleBranch->id]);
        }
    }
}
