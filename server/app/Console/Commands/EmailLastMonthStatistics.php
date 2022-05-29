<?php

namespace App\Console\Commands;

use App\Branch;
use Illuminate\Console\Command;

class EmailLastMonthStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:last-month-statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a email to all trainers with a statistic about the last month';

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
        $branches = Branch::get();
        foreach ($branches as $branch) {
            $this->call('emails:training-attendance-statistics', [
                'branchId' => $branch->id,
                'startDate' => date("Y-m-d", strtotime("first day of previous month")),
                'endDate' => date("Y-m-d", strtotime("last day of previous month")),
            ]);
        }
    }
}
