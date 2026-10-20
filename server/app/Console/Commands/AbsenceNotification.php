<?php

namespace App\Console\Commands;

use App\Services\PushNotifier;
use App\User;
use Illuminate\Console\Command;

class AbsenceNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:absence {userId} {from} {until} {absenceReason?}';

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
    public function handle(PushNotifier $notifier)
    {
        $notifier->absence(User::findOrFail($this->argument('userId')), $this->argument('from'), $this->argument('until'), $this->argument('absenceReason'));
    }
}
