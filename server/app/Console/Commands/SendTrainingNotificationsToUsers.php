<?php

namespace App\Console\Commands;

use App\Services\PushNotifier;
use Illuminate\Console\Command;

class SendTrainingNotificationsToUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:sendToUsersTrainingContext {userIds} {trainingId} {title} {data} {notificationType} {--storeStatus} {--url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a push notification to the given users with training context';

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
        $report = $notifier->toUsersForTraining(
            explode(',', $this->argument('userIds')),
            $this->argument('trainingId'),
            $this->argument('title'),
            $this->argument('data'),
            (int) $this->argument('notificationType'),
            (bool) $this->option('storeStatus'),
            $this->option('url'),
        );

        foreach ($report['disabled'] as $id) {
            $this->info('User #'.$id.' has disabled this notification type');
        }
        $this->info($report['recipients'].' Recipients found');
        if ($report['success'] !== null) {
            $this->info('Message successfully sent to '.$report['success'].' recipients');
            $this->info('Failed to send message to '.$report['failure'].' recipients');
        }
    }
}
