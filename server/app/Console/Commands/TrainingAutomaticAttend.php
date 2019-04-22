<?php

namespace App\Console\Commands;

use App\Training;
use App\User;
use Illuminate\Console\Command;

class TrainingAutomaticAttend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'training:automatic-attend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks all trainings that are tomorrow and automatically assigns all the users who are not yet assigned to them. Of course only the users which belong the correct training group. Users which are already actively chosen to attend or not are not touched.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $now = date('Y-m-d H:i:s');
        $tomorrow = date("Y-m-d H:i:s", time() + 86400);
        $closedTrainings = Training::whereBetween('start', array($now, $tomorrow))->get();

        foreach ($closedTrainings as $training) {
            $groups = $training->groups();
            $groupMembers = User::whereActive('1')->whereIn('group_id', $groups->pluck('group_id')->toArray())->get();

            foreach ($groupMembers as $groupMember) {
                //add all users who have not clicked on attending or not-attending
                if (!$training->participants()->where('user_id', $groupMember->id)->exists()) {
                    $training->participants()->attach($groupMember, ['attend' => 1]);
                }
            }
        }
    }
}
