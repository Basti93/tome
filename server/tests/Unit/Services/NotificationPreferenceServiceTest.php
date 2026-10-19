<?php

namespace Tests\Unit\Services;

use App\Services\NotificationPreferenceService;
use App\User;
use Database\Factories\UserFactory;
use Tests\TestCase;

class NotificationPreferenceServiceTest extends TestCase
{
    private NotificationPreferenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationPreferenceService;
    }

    public function test_defaults_enable_all_types(): void
    {
        $this->assertSame([
            'trainer_upcoming' => false,
            'trainer_cancel_alert' => true,
            'participant_upcoming' => true,
            'absence_alert' => true,
            'admin_messages' => true,
            'trainer_evaluation_reminder' => true,
            'trainer_evaluation_reminder_email' => false,
        ], $this->service->getDefaultPreferences());
    }

    public function test_users_without_stored_preferences_get_the_defaults(): void
    {
        $user = UserFactory::new()->create();

        $this->assertNull($user->notification_preferences);
        $this->assertSame($this->service->getDefaultPreferences(), $this->service->getPreferences($user));
    }

    public function test_every_type_but_the_opt_in_email_reminder_is_enabled_for_users_without_preferences(): void
    {
        $user = UserFactory::new()->create();

        foreach (array_keys($this->service->getDefaultPreferences()) as $type) {
            $this->assertSame(! in_array($type, User::OPT_IN_NOTIFICATION_TYPES, true), $this->service->isNotificationEnabled($user, $type), $type);
        }
        // older stored preferences without the key: opt-in types stay off, the others on
        $old = UserFactory::new()->create(['notification_preferences' => ['absence_alert' => false]]);
        $this->assertFalse($this->service->isNotificationEnabled($old, 'trainer_upcoming'));
        $this->assertFalse($this->service->isNotificationEnabled($old, 'trainer_evaluation_reminder_email'));
        $this->assertTrue($this->service->isNotificationEnabled($old, 'trainer_evaluation_reminder'));
        $this->assertTrue($this->service->isNotificationEnabled($user, 'some_unknown_type'), 'unknown types default to enabled');
    }

    public function test_update_merges_over_defaults_and_persists(): void
    {
        $user = UserFactory::new()->create();

        $this->service->updatePreferences($user, ['absence_alert' => false]);

        $fresh = $user->fresh();
        $this->assertFalse($fresh->notification_preferences['absence_alert']);
        $this->assertFalse($fresh->notification_preferences['trainer_upcoming'], 'the trainer reminder is opt-in');
        $this->assertTrue($fresh->notification_preferences['trainer_cancel_alert']);
        $this->assertFalse($this->service->isNotificationEnabled($fresh, 'absence_alert'));
        $this->assertTrue($this->service->isNotificationEnabled($fresh, 'admin_messages'));
    }

    public function test_update_replaces_earlier_choices(): void
    {
        $user = UserFactory::new()->create();
        $this->service->updatePreferences($user, ['absence_alert' => false]);

        $this->service->updatePreferences($user, ['trainer_upcoming' => false]);

        $this->assertTrue($user->fresh()->isNotificationEnabled('absence_alert'));
        $this->assertFalse($user->fresh()->isNotificationEnabled('trainer_upcoming'));
    }

    public function test_get_preferences_fills_missing_keys_with_the_defaults(): void
    {
        $user = UserFactory::new()->create(['notification_preferences' => ['absence_alert' => false]]);

        $this->assertSame(array_merge($this->service->getDefaultPreferences(), ['absence_alert' => false]), $this->service->getPreferences($user->fresh()));
        $this->assertTrue($user->fresh()->isNotificationEnabled('trainer_cancel_alert'), 'missing keys count as enabled');
        $this->assertFalse($user->fresh()->isNotificationEnabled('trainer_upcoming'), 'except the opt-in types');
    }
}
