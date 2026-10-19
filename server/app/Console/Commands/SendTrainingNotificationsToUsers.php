<?php

namespace App\Console\Commands;

use App\NotificationToken;
use App\Services\FirebaseMessagingService;
use App\Services\NotificationPreferenceService;
use App\User;
use App\UserTrainingNotification;
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
    public function handle()
    {
        $userIds = explode(',', $this->argument('userIds'));
        $trainingId = $this->argument('trainingId');
        $notificationType = $this->argument('notificationType');
        $title = $this->argument('title');
        $data = $this->argument('data');
        $url = $this->option('url');
        $storeStatus = $this->option('storeStatus');

        $this->info('storeStatus '.$this->option('storeStatus'));

        $notificationPreferenceService = new NotificationPreferenceService;
        $preferenceKey = $this->getPreferenceKeyForType($notificationType);

        $users = User::whereIn('id', $userIds)->get();
        $recipientTokens = [];
        foreach ($users as $user) {
            // Check if notification type is enabled for this user
            if ($preferenceKey && ! $notificationPreferenceService->isNotificationEnabled($user, $preferenceKey)) {
                $this->info('User '.$user->firstName.' '.$user->familyName.' has disabled this notification type');

                continue;
            }
            // check if notifications is already sent
            $this->info('User '.$user->firstName.' '.$user->familyName.' has '.count($user->notificationTokens).' tokens');
            if (! $storeStatus || ! UserTrainingNotification::whereType($notificationType)->where('user_id', $user->id)->where('training_id', $trainingId)->exists()) {
                foreach ($user->notificationTokens as $token) {
                    array_push($recipientTokens, $token->token);
                }
            } else {
                $this->info('Notification already sent to '.$user->firstName.' '.$user->familyName.'');

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

        $result = $firebaseService->sendToDevices($recipientTokens, $title, $data, $dataPayload);

        $this->info('Message successfully sent to '.$result['success'].' recipients');
        $this->info('Failed to send message to '.$result['failure'].' recipients');

        // Clean up invalid tokens
        foreach ($result['tokensToDelete'] as $tokenToDelete) {
            NotificationToken::whereToken($tokenToDelete)->delete();
        }

        if ($storeStatus) {
            // update notification table
            UserTrainingNotification::whereType($notificationType)->delete();
            foreach ($users as $user) {
                $utn = new UserTrainingNotification;
                $utn->user_id = $user->id;
                $utn->training_id = $trainingId;
                $utn->type = $notificationType;
                $utn->save();
            }
        }
    }

    /**
     * Map notification type to preference key.
     *
     * @param  int  $type
     * @return string|null
     */
    private function getPreferenceKeyForType($type)
    {
        $typeMap = [
            1 => 'trainer_upcoming',
            2 => 'trainer_cancel_alert',
            3 => 'participant_upcoming',
        ];

        return $typeMap[$type] ?? null;
    }
}
