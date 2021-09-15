<?php

namespace App\Console\Commands;

use App\Training;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AbsenceCleanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:absence-clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes all absences in the past from the users';

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
        $this->info('Startet at '.$now);
        $this->info('Check until '.$tomorrow);
        $closedTrainings = Training::whereBetween('start', array($now, $tomorrow))->where('automatic_attend', 1)->get();

        $this->info('Found  '.count($closedTrainings).' Trainings in the next 24h');

        foreach ($closedTrainings as $training) {
            $groups = $training->groups();
            $groupIds = $groups->pluck('group_id')->toArray();
            $groupMembers = User::whereActive(1)
                    ->whereHas('groups', function ($query) use ($groupIds) {
                        $query->whereIn('groups.id', $groupIds);
                })->get();
            $this->info('Found '.count($groupMembers).' possible attendies for training at '.$training->start);
            foreach ($groupMembers as $groupMember) {
                //add all users who have not clicked on attending or not-attending
                if (!$training->participants()->where('user_id', $groupMember->id)->exists()) {
                    $this->info('User with id '.$groupMember->id.' will automatically attend the training');
                    $training->participants()->attach($groupMember, ['attend' => 1]);
                } else {
                    $this->info('User with id '.$groupMember->id.' is already assigned to the training');
                }
            }
        }
    }
}
