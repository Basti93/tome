<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Training;
use App\UserTrainingNotification;
use DateTime;

class TrainerUpcomingTrainingNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trainer:upcomingTrainingNotifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $upcomingTrainings = Training::with('trainers', 'trainers.notificationTokens', 'participants')->whereBetween('start', array($now, $in4Hours))->get();

        $this->info('Found  '.count($upcomingTrainings).' Trainings in the next 4h ('.$now.') - ('.$in4Hours.')');

        foreach ($upcomingTrainings as $training) {
            $this->info('Processing Training at  '.$training->start);
            $recipients = [];
            foreach ($training->trainers as $trainer) {
                //check if notifications is already sent
                $this->info('Trainer ' . $trainer->firstName . ' ' . $trainer->familyName . ' has ' . count($trainer->notificationTokens) . ' tokens');
                if (!UserTrainingNotification::whereType(1)->where('user_id', $trainer->id)->where('training_id', $training->id)->exists()) {
                    foreach ($trainer->notificationTokens as $token) {
                        array_push($recipients, $token->token);
                    }
                } else {
                    $this->info('Notification already sent to ' . $trainer->firstName . ' ' . $trainer->familyName . '');
                }
            }
            $this->info('Recipients '.var_dump($recipients));
            fcm()
                ->to($recipients)
                ->data([
                    'title' => 'Dein Training heute um '.DateTime::createFromFormat('Y-m-d H:i:s', $training->start)->format("H:i"),
                    'body' => 'Es haben sich '.count($training->participants).' Sportler angemeldet'
                ])
                ->send();


            //update notification table
            UserTrainingNotification::whereType(1)->delete();
            foreach ($training->trainers as $trainer) {
                $utn = new UserTrainingNotification();
                $utn->user_id = $trainer->id;
                $utn->training_id = $training->id;
                $utn->type = 1;
                $utn->save();
            }
        }


    }
}
