<?php

namespace App\Console\Commands;

use App\NotificationToken;
use App\Services\FirebaseMessagingService;
use App\Services\NotificationPreferenceService;
use App\User;
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
    public function handle()
    {
        $userIds = explode(',', $this->argument('userIds'));
        $title = $this->argument('title');
        $body = $this->argument('data');
        $url = $this->option('url');
        $type = $this->option('type') ?: 'admin_messages';

        $notificationPreferenceService = new NotificationPreferenceService;
        $users = User::whereIn('id', $userIds)->get();
        $recipientTokens = [];

        foreach ($users as $user) {
            // Check if notification type is enabled for this user
            if (! $notificationPreferenceService->isNotificationEnabled($user, $type)) {
                $this->info('User '.'#'.$user->id.' has disabled '.$type.' notifications');

                continue;
            }
            $this->info('User '.'#'.$user->id.' has '.count($user->notificationTokens).' tokens');
            foreach ($user->notificationTokens as $token) {
                array_push($recipientTokens, $token->token);
            }
        }

        $this->info(count($recipientTokens).' Recipients found');
        if (empty($recipientTokens)) {
            return;
        }

        // Send via Firebase
        $firebaseService = new FirebaseMessagingService;
        $dataPayload = [
            'click_action' => empty($url) ? config('app.vue_url') : $url,
        ];

        $result = $firebaseService->sendToDevices($recipientTokens, $title, $body, $dataPayload);

        $this->info('Message successfully sent to '.$result['success'].' recipients');
        $this->info('Failed to send message to '.$result['failure'].' recipients');

        // Clean up invalid tokens
        foreach ($result['tokensToDelete'] as $tokenToDelete) {
            NotificationToken::whereToken($tokenToDelete)->delete();
        }
    }
}
