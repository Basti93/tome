<?php

namespace App\Console\Commands;

use App\NotificationToken;
use App\User;
use Illuminate\Console\Command;
use App\UserTrainingNotification;
use LaravelFCM\Facades\FCM;
use LaravelFCM\Message\PayloadDataBuilder;
use App\Services\NotificationPreferenceService;

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
        $data = $this->argument('data');
        $url = $this->option('url');
        $type = $this->option('type') ?: 'admin_messages';

        $notificationPreferenceService = new NotificationPreferenceService();
        $users = User::whereIn('id', $userIds)->get();
        $recipientTokens = [];
        foreach ($users as $user) {
            // Check if notification type is enabled for this user
            if (!$notificationPreferenceService->isNotificationEnabled($user, $type)) {
                $this->info('User ' . $user->firstName . ' ' . $user->familyName . ' has disabled ' . $type . ' notifications');
                continue;
            }
            //check if notifications is already sent
            $this->info('User ' . $user->firstName . ' ' . $user->familyName . ' has ' . count($user->notificationTokens) . ' tokens');
            foreach ($user->notificationTokens as $token) {
                array_push($recipientTokens, $token->token);
            }
        }
        $this->info(sizeof($recipientTokens) . ' Recipients found');
        if (empty($recipientTokens)) {
            return;
        }
        //TODO: create table for notification types and add the click_action url
        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData([
            'title' => $title,
            'body' => $data,
            'click_action' => empty($url) ? config('app.vue_url') : $url,
        ]);
        $data = $dataBuilder->build();
        $downstreamResponse = FCM::sendTo($recipientTokens, null, null, $data);

        $this->info('Message successfully send to ' . $downstreamResponse->numberSuccess() . ' recipients');
        $this->info('Failed to send message to ' . $downstreamResponse->numberFailure() . ' recipients');
        //clean up the unused tokens
        foreach ($downstreamResponse->tokensToDelete() as $tokenToDelete) {
            NotificationToken::whereToken($tokenToDelete)->delete();
        }

    }
}
