<?php

namespace App\Console\Commands;

use App\Services\PushNotifier;
use Illuminate\Console\Command;

class SendNotificationsToUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:sendToUsers {userIds} {title} {data} {--url} {--type=admin_messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a push notification to the given users';

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
        $type = $this->option('type') ?: 'admin_messages';
        $report = $notifier->toUsers(explode(',', $this->argument('userIds')), $this->argument('title'), $this->argument('data'), $this->option('url'), $type);

        foreach ($report['disabled'] as $id) {
            $this->info('User #'.$id.' has disabled '.$type.' notifications');
        }
        $this->info($report['recipients'].' Recipients found');
        if ($report['success'] !== null) {
            $this->info('Message successfully sent to '.$report['success'].' recipients');
            $this->info('Failed to send message to '.$report['failure'].' recipients');
        }
    }
}
