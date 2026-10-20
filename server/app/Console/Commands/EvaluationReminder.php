<?php

namespace App\Console\Commands;

use App\Mail\EvaluationReminder as EvaluationReminderMail;
use App\NotificationToken;
use App\Services\FirebaseMessagingService;
use App\Services\NotificationPreferenceService;
use App\Training;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Reminds the trainers of trainings that ended 2 hours to 7 days ago and are still not evaluated.
 * Every training is reminded at most twice: on the first run that finds it, and once more 3 days later.
 * Never contains athlete names. Push (preference trainer_evaluation_reminder, default on) and e-mail
 * (trainer_evaluation_reminder_email, default off) are chosen independently; one mail per trainer and run lists all
 * open trainings. A training only counts as reminded if at least one channel actually sent.
 */
class EvaluationReminder extends Command
{
    protected $signature = 'notification:evaluation-reminder';

    protected $description = 'Remind trainers of trainings that are not evaluated yet';

    public function handle(NotificationPreferenceService $preferences)
    {
        $trainings = Training::with(['trainers.notificationTokens', 'groups'])
            ->where('evaluated', false)
            ->whereBetween('end', [now()->subDays(7)->toDateTimeString(), now()->subHours(2)->toDateTimeString()])
            ->whereHas('trainers')
            ->where(function ($q) {
                $q->whereNull('evaluation_reminder_count')
                    ->orWhere('evaluation_reminder_count', 0)
                    ->orWhere(function ($q) {
                        $q->where('evaluation_reminder_count', 1)
                            ->whereDate('evaluation_reminder_sent_at', '<=', now()->subDays(3)->toDateString());
                    });
            })
            ->orderBy('start')
            ->get();

        // trainer id => [user, [trainings]]
        $perTrainer = [];
        foreach ($trainings as $training) {
            foreach ($training->trainers as $trainer) {
                if (! $trainer->active || ! $this->wantsAnyChannel($trainer, $preferences)) {
                    continue;
                }
                $perTrainer[$trainer->id]['user'] = $trainer;
                $perTrainer[$trainer->id]['trainings'][] = $training;
            }
        }

        $reminded = [];
        foreach ($perTrainer as $entry) {
            if ($this->remind($entry['user'], $entry['trainings'])) {
                foreach ($entry['trainings'] as $training) {
                    $reminded[$training->id] = $training;
                }
            }
        }

        foreach ($reminded as $training) {
            $training->evaluation_reminder_count = ((int) $training->evaluation_reminder_count) + 1;
            $training->evaluation_reminder_sent_at = now();
            $training->save();
        }

        $this->info(count($reminded).' Trainings erinnert, '.count($perTrainer).' Trainer');
    }

    /** @return bool whether at least one channel sent the reminder */
    private function remind($trainer, array $trainings): bool
    {
        $base = rtrim(config('app.vue_url'), '/');
        $items = array_map(fn (Training $t) => [
            'label' => $this->label($t),
            'url' => $base.'/#/quickEvaluation/'.$t->id,
        ], $trainings);

        $sent = false;
        $preferences = app(NotificationPreferenceService::class);

        $tokens = $trainer->notificationTokens->pluck('token')->all();
        if ($tokens !== [] && $preferences->isNotificationEnabled($trainer, 'trainer_evaluation_reminder')) {
            $count = count($items);
            $title = $count === 1 ? 'Training noch nicht abgeschlossen' : $count.' Trainings noch nicht abgeschlossen';
            $body = implode("\n", array_column($items, 'label'));
            $result = (new FirebaseMessagingService)->sendToDevices($tokens, $title, $body, [
                'click_action' => $count === 1 ? $items[0]['url'] : $base.'/#/trainingsEvaluation',
            ]);
            foreach ($result['tokensToDelete'] as $token) {
                NotificationToken::whereToken($token)->delete();
            }
            if ($result['success'] > 0) {
                $sent = true;
            }
        }

        if (! empty($trainer->email) && $preferences->isNotificationEnabled($trainer, 'trainer_evaluation_reminder_email')) {
            Mail::to($trainer->email)->send(new EvaluationReminderMail($trainer, $items));
            $sent = true;
        }

        return $sent;
    }

    private function wantsAnyChannel($trainer, NotificationPreferenceService $preferences): bool
    {
        return $preferences->isNotificationEnabled($trainer, 'trainer_evaluation_reminder')
            || $preferences->isNotificationEnabled($trainer, 'trainer_evaluation_reminder_email');
    }

    private function label(Training $t): string
    {
        $start = Carbon::parse($t->start);
        $end = Carbon::parse($t->end);
        $days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
        $label = $days[$start->dayOfWeek].' '.$start->format('d.m.').' '.$start->format('H:i').'-'.$end->format('H:i');
        $groups = $t->groups->pluck('name')->implode(', ');

        return $groups === '' ? $label : $label.' ('.$groups.')';
    }
}
