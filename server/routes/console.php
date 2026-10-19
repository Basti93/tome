<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Schedule
|--------------------------------------------------------------------------
|
| Commands in app/Console/Commands are discovered automatically. The scheduler
| is driven by `php artisan schedule:run` (cron, every minute).
| tests/Feature/ScheduleTest.php asserts the registered events.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Schedule::command('training:automatic-attend')->everyThirtyMinutes()->between('6:00', '22:00')->appendOutputTo(storage_path('logs/com_automatic_attend.log'));

// Reminders can be switched off per user (notification preferences: trainer_upcoming, participant_upcoming)
Schedule::command('notification:upcomingTrainingForTrainer')->everyThirtyMinutes()->between('6:00', '22:00')->appendOutputTo(storage_path('logs/com_upcoming_trainings_for_trainers.log'));
Schedule::command('notification:upcomingTraining')->everyThirtyMinutes()->between('6:00', '22:00')->appendOutputTo(storage_path('logs/com_upcoming_trainings.log'));

// Once a day: trainers get one message listing their trainings that are still not evaluated (preference: trainer_evaluation_reminder)
Schedule::command('notification:evaluation-reminder')->dailyAt('20:00')->appendOutputTo(storage_path('logs/com_evaluation_reminder.log'));

Schedule::command('training:series')->hourly()->sendOutputTo(storage_path('logs/com_series.log'));
Schedule::command('user:absence-clean')->dailyAt('1:00')->sendOutputTo(storage_path('logs/com_absence_clean.log'));
Schedule::command('emails:last-month-statistics')->monthlyOn(1)->appendOutputTo(storage_path('logs/com_email_monthly_statistics.log'));

// GDPR data retention (inactive users, old participations, tokens, auth logs); prints counts only
// Off until RETENTION_ENABLED=true (see config/tome.php); `php artisan tome:retention --dry-run` always works.
Schedule::command('tome:retention')->monthlyOn(1, '03:00')->appendOutputTo(storage_path('logs/com_retention.log'))
    ->when(fn () => (bool) config('tome.retention.enabled'));
