<?php

namespace Tests\Unit\Services;

use App\Services\NotificationPreferenceService;
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

    public function test_defaults_enable_all_five_types(): void
    {
        $this->assertSame([
            'trainer_upcoming' => true,
            'trainer_cancel_alert' => true,
            'participant_upcoming' => true,
            'absence_alert' => true,
            'admin_messages' => true,
        ], $this->service->getDefaultPreferences());
    }

    public function test_users_without_stored_preferences_get_the_defaults(): void
    {
        $user = UserFactory::new()->create();

        $this->assertNull($user->notification_preferences);
        $this->assertSame($this->service->getDefaultPreferences(), $this->service->getPreferences($user));
    }

    public function test_every_type_is_enabled_for_users_without_preferences(): void
    {
        $user = UserFactory::new()->create();

        foreach (array_keys($this->service->getDefaultPreferences()) as $type) {
            $this->assertTrue($this->service->isNotificationEnabled($user, $type));
        }
        $this->assertTrue($this->service->isNotificationEnabled($user, 'some_unknown_type'), 'unknown types default to enabled');
    }

    public function test_update_merges_over_defaults_and_persists(): void
    {
        $user = UserFactory::new()->create();

        $this->service->updatePreferences($user, ['absence_alert' => false]);

        $fresh = $user->fresh();
        $this->assertFalse($fresh->notification_preferences['absence_alert']);
        $this->assertTrue($fresh->notification_preferences['trainer_upcoming']);
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

    public function test_get_preferences_returns_the_stored_array_verbatim(): void
    {
        $user = UserFactory::new()->create(['notification_preferences' => ['absence_alert' => false]]);

        $this->assertSame(['absence_alert' => false], $this->service->getPreferences($user->fresh()));
        $this->assertTrue($user->fresh()->isNotificationEnabled('trainer_upcoming'), 'missing keys count as enabled');
    }
}
