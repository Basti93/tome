<?php

namespace App\Console\Commands;

use App\Mail\UserTrainingStatistics;
use App\Services\TrainingStatisticsReport;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
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
    public function handle(TrainingStatisticsReport $statistics)
    {
        $branchId = (int) $this->argument('branchId');
        $from = Carbon::createFromFormat('Y-m-d', $this->argument('startDate'))->startOfDay();
        $until = Carbon::createFromFormat('Y-m-d', $this->argument('endDate'))->endOfDay();

        $report = $statistics->forBranch($branchId, $from, $until);

        $branchTrainers = User::role('trainer')
            ->whereHas('trainerBranches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->get();

        foreach ($branchTrainers as $branchTrainer) {
            if ($branchTrainer->email && $branchTrainer->isNotificationEnabled('trainer_monthly_statistics')) {
                Mail::to($branchTrainer)->send(new UserTrainingStatistics($statistics->forTrainer($report, $branchTrainer)));
            }
        }
    }
}
