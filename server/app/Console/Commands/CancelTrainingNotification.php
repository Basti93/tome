<?php

namespace App\Console\Commands;

use App\Services\PushNotifier;
use App\Training;
use App\User;
use Illuminate\Console\Command;

class CancelTrainingNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:cancelTrainingForTrainer {userId} {trainingId} {cancelReason}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a push notification to the responsible trainer if a user cancel a training 24 hours in advance';

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
        $notifier->trainingCancelled(Training::findOrFail($this->argument('trainingId')), User::findOrFail($this->argument('userId')), $this->argument('cancelReason'));
    }
}
