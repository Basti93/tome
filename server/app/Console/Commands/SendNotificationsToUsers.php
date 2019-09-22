<?php

namespace App\Console\Commands;

use App\NotificationToken;
use App\User;
use Illuminate\Console\Command;
use App\UserTrainingNotification;
use LaravelFCM\Facades\FCM;
use LaravelFCM\Message\PayloadDataBuilder;

class SendNotificationsToUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:sendToUsers {userIds} {trainingId} {title} {data} {notificationType} {--storeStatus}';

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
        $trainingId = $this->argument('trainingId');
        $notificationType = $this->argument('notificationType');
        $title = $this->argument('title');
        $data = $this->argument('data');
        $storeStatus = $this->option('storeStatus');

        $this->info('storeStatus '.$this->option('storeStatus'));

        $users = User::whereIn('id', $userIds)->get();
        $recipientTokens = [];
        foreach ($users as $user) {
            //check if notifications is already sent
            $this->info('User ' . $user->firstName . ' ' . $user->familyName . ' has ' . count($user->notificationTokens) . ' tokens');
            if (!$storeStatus || !UserTrainingNotification::whereType($notificationType)->where('user_id', $user->id)->where('training_id', $trainingId)->exists()) {
                foreach ($user->notificationTokens as $token) {
                    array_push($recipientTokens, $token->token);
                }
            } else {
                $this->info('Notification already sent to ' . $user->firstName . ' ' . $user->familyName . '');

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
            'click_action' => 'https://training.ssc-landau.de',
        ]);
        $data = $dataBuilder->build();
        $downstreamResponse = FCM::sendTo($recipientTokens, null, null, $data);

        $this->info('Message successfully send to ' . $downstreamResponse->numberSuccess() . ' recipients');
        $this->info('Failed to send message to ' . $downstreamResponse->numberFailure() . ' recipients');
        //clean up the unused tokens
        foreach ($downstreamResponse->tokensToDelete() as $tokenToDelete) {
            NotificationToken::whereToken($tokenToDelete)->delete();
        }

        if ($storeStatus) {
            //update notification table
            UserTrainingNotification::whereType($notificationType)->delete();
            foreach ($users as $user) {
                $utn = new UserTrainingNotification();
                $utn->user_id = $user->id;
                $utn->training_id = $trainingId;
                $utn->type = $notificationType;
                $utn->save();
            }
        }
    }
}
