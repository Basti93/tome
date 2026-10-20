<?php

namespace App\Services;

use App\Training;
use App\User;
use Illuminate\Support\Facades\DB;

/** Who attends a training: check-in, check-out (with the short-notice cancellation rule) and the attendee list. */
class TrainingAttendance
{
    public function __construct(private PushNotifier $notifier) {}

    /** True if the user belongs to one of the groups of the training (only they may check in or out). */
    public function isAssigned(Training $training, User $user): bool
    {
        return $training->groups()->whereIn('groups.id', $user->getGroupIdsAttribute())->exists();
    }

    public function checkIn(Training $training, int|string $userId): void
    {
        $this->setAttend($training, $userId, 1);
    }

    /**
     * Check a user out. A cancellation within the next 24 hours needs a reason and notifies the trainers.
     *
     * @return bool false if a reason is required but missing (nothing was changed)
     */
    public function checkOut(Training $training, User $user, ?string $reason): bool
    {
        $start = strtotime($training->start);
        $shortNotice = $start > time() && $start < time() + 86400;

        if (! $shortNotice) {
            $this->setAttend($training, $user->id, 0);

            return true;
        }
        if (empty($reason)) {
            return false;
        }

        $this->setAttend($training, $user->id, 0, ['cancelreason' => $reason]);
        $this->notifier->trainingCancelled($training, $user, $reason);

        return true;
    }

    /** Exactly these users attend; everybody else on the list is set to absent. */
    public function setAttendees(Training $training, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->checkIn($training, $userId);
        }

        DB::table('training_participation')
            ->whereNotIn('user_id', $userIds)
            ->where('training_id', $training->id)
            ->update(['attend' => 0]);
    }

    private function setAttend(Training $training, int|string $userId, int $attend, array $extra = []): void
    {
        $values = ['attend' => $attend] + $extra;

        if ($training->participants->contains($userId)) {
            $training->participants()->updateExistingPivot($userId, $values, false);
        } else {
            $training->participants()->attach($userId, $values);
        }
    }
}
