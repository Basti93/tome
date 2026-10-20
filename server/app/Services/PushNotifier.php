<?php

namespace App\Services;

use App\NotificationToken;
use App\Training;
use App\TrainingParticipation;
use App\User;
use App\UserTrainingNotification;
use DateTime;

/**
 * Sends push notifications to users, respecting their notification preferences. Used by the HTTP controllers
 * and by the artisan commands (which only print the returned report).
 *
 * Every send method returns ['disabled' => user ids that opted out, 'recipients' => token count,
 * 'success' => int|null, 'failure' => int|null] (success/failure are null if nothing was sent).
 */
class PushNotifier
{
    /** notification type number (stored in user_training_notification) => preference key */
    private const TRAINING_PREFERENCE_KEYS = [
        1 => 'trainer_upcoming',
        2 => 'trainer_cancel_alert',
        3 => 'participant_upcoming',
    ];

    /**
     * @param  array<int, int|string>  $userIds
     * @return array{disabled: array<int, int>, recipients: int, success: int|null, failure: int|null}
     */
    public function toUsers(array $userIds, string $title, string $body, ?string $url = null, string $type = 'admin_messages'): array
    {
        $preferences = new NotificationPreferenceService;
        $tokens = [];
        $disabled = [];

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            if (! $preferences->isNotificationEnabled($user, $type)) {
                $disabled[] = $user->id;

                continue;
            }
            foreach ($user->notificationTokens as $token) {
                $tokens[] = $token->token;
            }
        }

        return $this->deliver($tokens, $title, $body, $url) + ['disabled' => $disabled];
    }

    /**
     * Like toUsers() for a training. With $storeStatus every (user, training, type) is notified only once.
     *
     * @param  array<int, int|string>  $userIds
     * @return array{disabled: array<int, int>, recipients: int, success: int|null, failure: int|null}
     */
    public function toUsersForTraining(array $userIds, int|string $trainingId, string $title, string $body, int $type, bool $storeStatus = false, ?string $url = null): array
    {
        $preferences = new NotificationPreferenceService;
        $preferenceKey = self::TRAINING_PREFERENCE_KEYS[$type] ?? null;
        $tokens = [];
        $disabled = [];
        $processed = [];

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            if ($preferenceKey && ! $preferences->isNotificationEnabled($user, $preferenceKey)) {
                $disabled[] = $user->id;

                continue;
            }
            $processed[] = $user->id;
            if (! $storeStatus || ! UserTrainingNotification::whereType($type)->where('user_id', $user->id)->where('training_id', $trainingId)->exists()) {
                foreach ($user->notificationTokens as $token) {
                    $tokens[] = $token->token;
                }
            }
        }

        $report = $this->deliver($tokens, $title, $body, $url) + ['disabled' => $disabled];

        if ($storeStatus) {
            $this->markAsSent($processed, $trainingId, $type);
        }

        return $report;
    }

    /** Tell the trainers of the training that a participant cancelled at short notice. */
    public function trainingCancelled(Training $training, User $user, string $reason): void
    {
        $this->toUsersForTraining(
            $training->trainers()->pluck('user_id')->all(),
            $training->id,
            'Absage für das Training um '.DateTime::createFromFormat('Y-m-d H:i:s', $training->start)->format('H:i').' Uhr',
            'Sportler: '.$user->firstName.' '.$user->familyName."\r\nGrund: ".$reason."\r\nBis jetzt angemeldet: ".TrainingParticipation::whereTrainingId($training->id)->whereAttend(1)->count().' Sportler',
            2,
            false,
            config('app.vue_url').'/#/trainingsPrepare',
        );
    }

    /** Tell the trainers of the user's branches that the user is absent. */
    public function absence(User $user, string $from, string $until, ?string $reason = null): void
    {
        $branchIds = $user->groups()->pluck('branch_id')->toArray();
        $trainerIds = User::role('trainer')
            ->when($branchIds, function ($query, $branchIds) {
                $query->whereHas('trainerBranches', function ($query) use ($branchIds) {
                    $query->whereIn('branch_id', $branchIds);
                });
            })->pluck('id')->all();

        $this->toUsers(
            $trainerIds,
            'Abwesenheit '.$user->firstName.' '.$user->familyName,
            'Von '.$from.' bis '.$until.(empty($reason) ? '' : "\r\nGrund: ".$reason),
            config('app.vue_url').'/#/absenceForm',
            'absence_alert',
        );
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array{recipients: int, success: int|null, failure: int|null}
     */
    private function deliver(array $tokens, string $title, string $body, ?string $url): array
    {
        if (empty($tokens)) {
            return ['recipients' => 0, 'success' => null, 'failure' => null];
        }

        $result = (new FirebaseMessagingService)->sendToDevices($tokens, $title, $body, [
            'click_action' => empty($url) ? config('app.vue_url') : $url,
        ]);

        foreach ($result['tokensToDelete'] as $tokenToDelete) {
            NotificationToken::whereToken($tokenToDelete)->delete();
        }

        return ['recipients' => count($tokens), 'success' => $result['success'], 'failure' => $result['failure']];
    }

    /**
     * Remember who was already notified about this training so that the reminder (which runs every
     * 30 minutes) is sent once. Only markers of this training are touched.
     *
     * @param  array<int, int>  $userIds
     */
    private function markAsSent(array $userIds, int|string $trainingId, int $type): void
    {
        foreach ($userIds as $userId) {
            UserTrainingNotification::firstOrCreate(['type' => $type, 'user_id' => $userId, 'training_id' => $trainingId]);
        }
    }
}
