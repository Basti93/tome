<?php

namespace App\Console\Commands;

use App\Group;
use App\Mail\UserTrainingStatistics;
use App\Training;
use App\User;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailTrainingAttendanceStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:training-attendance-statistics {branchId} ${startDate} ${endDate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a email to all trainers of the given branch with a statistic about the training attendance';

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
        $branchId = $this->argument('branchId');
        $from = DateTime::createFromFormat('Y-m-d', $this->argument('startDate'));
        $until = DateTime::createFromFormat('Y-m-d', $this->argument('endDate'));

        $groups = Group::whereBranchId($branchId)->get();
        Log::info('Groups found for branchId ' . $branchId . ": " . sizeof($groups));

        $groupsArray = [];
        foreach ($groups as $group) {
            $groupId = $group->id;
            $groupUsers = User::whereActive('1')->whereHas('groups', function ($query) use ($groupId) {
                $query->whereGroupId($groupId);
            })->get();

            Log::info('Users found for group id ' . $groupId . ": " . sizeof($groupUsers));

            $userArray = [];
            foreach ($groupUsers as $groupUser) {
                $userId = $groupUser->id;
                $trainingsCount = Training::whereBetween('start', array($from, $until))
                    ->where('deleted', false)
                    ->where('evaluated', true)
                    ->whereHas('groups', function ($query) use ($groupId) {
                        $query->where('group_id', $groupId);
                    })
                    ->whereHas('participants', function ($query) use ($userId) {
                        $query->where('user_id', $userId)->whereAttend(1);
                    })
                    ->count();
                Log::info('Trainings found for userid ' . $userId . " between " . $from->format('Y-m-d') . " - " . $until->format('Y-m-d') . ": " . $trainingsCount);
                $userArray[$groupUser->firstName . " " . $groupUser->familyName] = $trainingsCount;
            }
            uasort($userArray, function ($a, $b) {
                return $b - $a;
            });

            $groupsArray[$group->name] = $userArray;
        }

        $branchTrainers = User::role('trainer')
            ->whereHas('trainerBranches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->get();

        foreach ($branchTrainers as $branchTrainer) {
            if ($branchTrainer->email) {
                Log::info("Send monthly training statistics to " . $branchTrainer->email);
                Mail::to($branchTrainer)->send(new UserTrainingStatistics($branchTrainer, $groupsArray, $from, $until));
            }
        }

    }
}
