<?php

namespace App\Console\Commands;

use App\NotificationToken;
use App\TrainingParticipation;
use Illuminate\Console\Command;
use App\Training;
use App\UserTrainingNotification;
use DateTime;
use LaravelFCM\Facades\FCM;

class TrainerUpcomingTrainingNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:upcomingTrainingForTrainer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a push notification to trainers 4 hours before a training with the info how many users are attending';

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
        $in4Hours = date("Y-m-d H:i:s", time() + 14400);
        $upcomingTrainings = Training::with('trainers', 'trainers.notificationTokens', 'participants')
                                        ->whereBetween('start', array($now, $in4Hours))
                                        ->get();
        $this->info('Found  ' . count($upcomingTrainings) . ' Trainings in the next 4h (' . $now . ') - (' . $in4Hours . ')');

        foreach ($upcomingTrainings as $training) {
            $this->info('Processing Training at  ' . $training->start);
            $trainerArrayString = implode(",", $training->trainers()->pluck('user_id')->toArray());
            $this->info('Trainers with ids  ' . $trainerArrayString);
            $this->call('sendToUsersTrainingContext', [
                'userIds' => $trainerArrayString,
                'trainingId' => $training->id,
                '--url' => config('app.vue_url').'/#/trainingsPrepare',
                'title' => 'Dein Training heute um ' . DateTime::createFromFormat('Y-m-d H:i:s', $training->start)->format("H:i"),
                'data' => 'Es haben sich ' . TrainingParticipation::whereTrainingId($training->id)->whereAttend(1)->count() . ' Sportler angemeldet',
                'notificationType' => 1,
                '--storeStatus' => true
            ]);
        }

    }
}
