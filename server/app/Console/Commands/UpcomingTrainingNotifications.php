<?php

namespace App\Console\Commands;

use App\Location;
use Illuminate\Console\Command;
use App\Training;
use DateTime;

class UpcomingTrainingNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:upcomingTraining';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a push notification to users 4 hours before a training';

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
        $upcomingTrainings = Training::with('participants', 'participants.notificationTokens')->whereBetween('start', array($now, $in4Hours))->get();
        $this->info('Found  ' . count($upcomingTrainings) . ' Trainings in the next 4h (' . $now . ') - (' . $in4Hours . ')');

        foreach ($upcomingTrainings as $training) {
            $location = Location::whereId($training->location_id)->first();
            $this->info('Processing Training at  ' . $training->start);
            $this->call('notification:sendToUsers', [
                'userIds' => implode("','", $training->participants()->whereAttend(1)->pluck('training_participation.user_id')->toArray()),
                'trainingId' => $training->id,
                'title' => 'Erinnerung: Dein Training startet in Kürze ',
                'data' => "Zeit: " . DateTime::createFromFormat('Y-m-d H:i:s', $training->start)->format("H:i") . "\r\nOrt: " . $location->name,
                'notificationType' => 3,
                '--storeStatus' => true
            ]);
        }

    }
}
