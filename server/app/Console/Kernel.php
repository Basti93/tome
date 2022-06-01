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
        'App\Console\Commands\SendTrainingNotificationsToUsers',
        'App\Console\Commands\TestNotification',
        'App\Console\Commands\CancelTrainingNotification',
        'App\Console\Commands\TrainerUpcomingTrainingNotifications',
        'App\Console\Commands\UpcomingTrainingNotifications',
        'App\Console\Commands\AbsenceNotification',
        'App\Console\Commands\AbsenceCleanCommand',
        'App\Console\Commands\EmailTrainingAttendanceStatistics',
        'App\Console\Commands\EmailLastMonthStatistics',

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('training:automatic-attend')->everyThirtyMinutes()->between('6:00', '22:00')->appendOutputTo(storage_path('logs/com_automatic_attend.log'));
        //$schedule->command('notification:upcomingTrainingForTrainer')->everyThirtyMinutes()->between('6:00', '22:00')->appendOutputTo(storage_path('logs/com_upcoming_trainings_for_trainers.log'));
        //$schedule->command('notification:upcomingTraining')->everyThirtyMinutes()->between('6:00', '22:00')->appendOutputTo(storage_path('logs/com_upcoming_trainings.log'));
        $schedule->command('training:series')->hourly()->sendOutputTo(storage_path('logs/com_series.log'));
        $schedule->command('user:absence-clean')->dailyAt('1:00')->sendOutputTo(storage_path('logs/com_absence_clean.log'));
        $schedule->command('emails:last-month-statistics')->monthlyOn(1)->appendOutputTo(storage_path('logs/com_email_monthly_statistics.log'));
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
