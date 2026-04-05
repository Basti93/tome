<?php

namespace App\Services;

use App\User;

class NotificationPreferenceService
{
    /**
     * Check if a notification type is enabled for a user.
     *
     * @param User $user
     * @param string $type
     * @return bool
     */
    public function isNotificationEnabled(User $user, $type)
    {
        return $user->isNotificationEnabled($type);
    }

    /**
     * Get all notification preferences for a user.
     *
     * @param User $user
     * @return array
     */
    public function getPreferences(User $user)
    {
        return $user->notification_preferences ?? $this->getDefaultPreferences();
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
        ];
    }

    /**
     * Update notification preferences for a user.
     *
     * @param User $user
     * @param array $preferences
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
