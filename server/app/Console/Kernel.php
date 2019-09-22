<?php

namespace App\Console;

use App\Console\Commands\TrainingAutomaticAttend;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Api\V1\Controllers\TrainingController;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\TrainingAutomaticAttend',
        'App\Console\Commands\TrainingSeriesCommand',
        'App\Console\Commands\SendNotificationsToUsers',
        'App\Console\Commands\CancelTrainingNotification',
        'App\Console\Commands\TrainerUpcomingTrainingNotifications',
        'App\Console\Commands\UpcomingTrainingNotifications',

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('training:automatic-attend')->dailyAt('00:00')->sendOutputTo(storage_path('logs/com_automatic_attend.log'));
        $schedule->command('training:series')->hourly()->sendOutputTo(storage_path('logs/com_series.log'));
        $schedule->command('notification:upcomingTrainingForTrainer')->everyThirtyMinutes()->unlessBetween('22:00', '6:00')->sendOutputTo(storage_path('logs/com_ucoming_trainings_for_trainers.log'));
        $schedule->command('notification:upcomingTraining')->everyThirtyMinutes()->unlessBetween('22:00', '6:00')->sendOutputTo(storage_path('logs/com_upcoming_trainings.log'));
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
