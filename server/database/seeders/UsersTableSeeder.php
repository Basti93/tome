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

        // Get all groups and branches
        $groups = Group::all();
        $maleBranch = Branch::where('name', 'Gerätturnen männlich')->first();
        $femaleBranch = Branch::where('name', 'Gerätturnen weiblich')->first();
        $maleGroups = $maleBranch ? Group::where('branch_id', $maleBranch->id)->get() : collect();
        $femaleGroups = $femaleBranch ? Group::where('branch_id', $femaleBranch->id)->get() : collect();

        // Assign base users to groups
        if ($groups->isNotEmpty()) {
            $member->groups()->syncWithoutDetaching([$groups->first()->id]);
            $trainer->groups()->syncWithoutDetaching([$groups->first()->id]);
            $support->groups()->syncWithoutDetaching([$groups->first()->id]);
        }

        if ($femaleBranch) {
            $trainer->trainerBranches()->syncWithoutDetaching([$femaleBranch->id]);
            $support->trainerBranches()->syncWithoutDetaching([$femaleBranch->id]);
        }

        // Create 30 additional members and assign to male groups
        $maleMembers = [];
        for ($i = 1; $i <= 30; $i++) {
            $user = User::firstOrNew(['email' => "male_member_{$i}@tome.local"]);
            $user->firstName = "Male Member";
            $user->familyName = "User {$i}";
            $user->password = 'password';
            $user->active = 1;
            $user->registered = 1;
            $user->save();
            $user->assignRole('member');

            if ($maleGroups->isNotEmpty()) {
                $randomGroup = $maleGroups->random();
                $user->groups()->syncWithoutDetaching([$randomGroup->id]);
            }
            $maleMembers[] = $user;
        }

        // Create 30 additional members and assign to female groups
        $femaleMembers = [];
        for ($i = 1; $i <= 30; $i++) {
            $user = User::firstOrNew(['email' => "female_member_{$i}@tome.local"]);
            $user->firstName = "Female Member";
            $user->familyName = "User {$i}";
            $user->password = 'password';
            $user->active = 1;
            $user->registered = 1;
            $user->save();
            $user->assignRole('member');

            if ($femaleGroups->isNotEmpty()) {
                $randomGroup = $femaleGroups->random();
                $user->groups()->syncWithoutDetaching([$randomGroup->id]);
            }
            $femaleMembers[] = $user;
        }

        // Create 5 additional trainers and assign to branches
        for ($i = 1; $i <= 5; $i++) {
            $user = User::firstOrNew(['email' => "trainer_{$i}@tome.local"]);
            $user->firstName = "Additional Trainer";
            $user->familyName = "{$i}";
            $user->password = 'password';
            $user->active = 1;
            $user->registered = 1;
            $user->save();
            $user->assignRole('trainer');

            if ($i % 2 === 0 && $maleBranch) {
                $user->trainerBranches()->syncWithoutDetaching([$maleBranch->id]);
            } elseif ($femaleBranch) {
                $user->trainerBranches()->syncWithoutDetaching([$femaleBranch->id]);
            }
        }
    }
}
