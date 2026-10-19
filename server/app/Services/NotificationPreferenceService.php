<?php

namespace App\Services;

use App\User;

class NotificationPreferenceService
{
    /**
     * Check if a notification type is enabled for a user.
     *
     * @param  string  $type
     * @return bool
     */
    public function isNotificationEnabled(User $user, $type)
    {
        return $user->isNotificationEnabled($type);
    }

    /**
     * Get all notification preferences for a user.
     *
     * @return array
     */
    public function getPreferences(User $user)
    {
        return array_merge($this->getDefaultPreferences(), $user->notification_preferences ?? []);
    }

    /**
     * Get default notification preferences.
     *
     * @return array
     */
    public function getDefaultPreferences()
    {
        return [
            'trainer_upcoming' => true,
            'trainer_cancel_alert' => true,
            'participant_upcoming' => true,
            'absence_alert' => true,
            'admin_messages' => true,
            'trainer_evaluation_reminder' => true,
            'trainer_evaluation_reminder_email' => false,
        ];
    }

    /**
     * Update notification preferences for a user.
     *
     * @return User
     */
    public function updatePreferences(User $user, array $preferences)
    {
        $user->notification_preferences = array_merge(
            $this->getDefaultPreferences(),
            $preferences
        );
        $user->save();

        return $user;
    }
}
