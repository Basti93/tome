<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;


class AbsenceNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:absence {userId} {from} {until} {absenceReason}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a push notification to the responsible trainers if a user is absence (vacation etc.)';

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
        $userId = $this->argument('userId');
        $from = $this->argument('from');
        $until = $this->argument('until');
        $absenceReason = $this->argument('absenceReason');

        $user = User::findOrFail($userId);

        $branchIds = $user->groups()->pluck('branch_id')->toArray();
        $trainers = User::role('trainer')
            ->when($branchIds, function ($query, $branchIds) {
                $query->whereHas('trainerBranches', function ($query) use ($branchIds) {
                    $query->whereIn('branch_id', $branchIds);
                });
            })->get();;

        $trainerArrayString = implode(",", $trainers->pluck('id')->toArray());

        $this->info('Trainers for branch ids '.implode(",", $branchIds).' with ids  ' . $trainerArrayString);
        $this->info('From '.$from);
        $this->info('Until '.$until);

        $this->call('notification:sendToUsers', [
            'userIds' => $trainerArrayString,
            'title' => 'Abwesenheit ' . $user->firstName . " " . $user->familyName,
            'data' => "Von " . $from . " bis " . $until . "\r\nGrund: " . $absenceReason,
            '--url' => config('app.vue_url') . '/#/absenceForm',
        ]);


    }
}
