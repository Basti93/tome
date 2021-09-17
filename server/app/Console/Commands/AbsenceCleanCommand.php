<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

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
        $this->info('Startet at ' . $now);
        $absenceInThePast = User::where('absenceEnd', '<=', $now)->get();

        $this->info('Found  ' . count($absenceInThePast) . ' users with absence in the past');

        foreach ($absenceInThePast as $userToClean) {
            $userToClean->absenceStart = null;
            $userToClean->absenceEnd = null;
            $userToClean->absenceReason = null;
            $userToClean->save();
        }
    }
}
