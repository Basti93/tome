<?php

namespace App\Console\Commands;

use App\TrainingParticipation;
use App\User;
use Illuminate\Console\Command;
use App\Training;
use App\UserTrainingNotification;
use DateTime;
use LaravelFCM\Facades\FCM;

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
    public function handle()
    {
        $userId = $this->argument('userId');
        $trainingId = $this->argument('trainingId');
        $cancelReason = $this->argument('cancelReason');

        $training = Training::findOrFail($trainingId);
        $user = User::findOrFail($userId);
        $trainerArrayString = implode(",", $training->trainers()->pluck('user_id')->toArray());
        $this->info('Trainers with ids  ' . $trainerArrayString);
        $this->call('notification:sendToUsers', [
            'userIds' => $trainerArrayString,
            'trainingId' => $training->id,
            'title' => 'Absage für das Training um ' . DateTime::createFromFormat('Y-m-d H:i:s', $training->start)->format("H:i").' Uhr',
            'data' => "Sportler: ".$user->firstName . " " . $user->familyName  ."\r\nGrund: " . $cancelReason . "\r\nBis jetzt angemeldet: " . TrainingParticipation::whereTrainingId($training->id)->whereAttend(1)->count() . " Sportler",
            'notificationType' => 2,
            '--url' => config('app.vue_url').'/#/trainingsPrepare',
        ]);


    }
}
