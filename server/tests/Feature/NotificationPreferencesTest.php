<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * GET|PUT /api/v1/user/me/notification-preferences. Request keys are snake_case
 * (`notification_preferences`, `absence_alert`), response keys are camelCased by the JSON middleware.
 */
class NotificationPreferencesTest extends TestCase
{
    private const URI = '/api/v1/user/me/notification-preferences';

    private const DEFAULTS = [
        'trainerUpcoming' => true,
        'trainerCancelAlert' => true,
        'participantUpcoming' => true,
        'absenceAlert' => true,
        'adminMessages' => true,
        'trainerEvaluationReminder' => true,
        'trainerEvaluationReminderEmail' => false,
    ];

    public function test_requires_authentication(): void
    {
        $this->getJson(self::URI)->assertStatus(401);
        $this->putJson(self::URI, [])->assertStatus(401);
    }

    public function test_get_returns_all_enabled_by_default_for_any_role(): void
    {
        foreach (['member', 'trainer', 'admin'] as $role) {
            $this->actingAsJwt($this->makeUser($role))->getJson(self::URI)
                ->assertOk()
                ->assertExactJson(['notificationPreferences' => self::DEFAULTS]);
        }
    }

    public function test_put_disables_a_type_and_persists_it(): void
    {
        $user = $this->makeUser('trainer');

        $this->actingAsJwt($user)->putJson(self::URI, ['notification_preferences' => ['absence_alert' => false]])
            ->assertOk()
            ->assertExactJson(['status' => 'ok', 'notificationPreferences' => array_merge(self::DEFAULTS, ['absenceAlert' => false])]);

        $this->assertFalse($user->fresh()->notification_preferences['absence_alert']);
        $this->actingAsJwt($user)->getJson(self::URI)->assertJsonPath('notificationPreferences.absenceAlert', false)
            ->assertJsonPath('notificationPreferences.trainerUpcoming', true);
    }

    public function test_put_merges_over_defaults_so_omitted_types_are_re_enabled(): void
    {
        $user = $this->makeUser('trainer');
        $this->actingAsJwt($user)->putJson(self::URI, ['notification_preferences' => ['absence_alert' => false]])->assertOk();

        $this->actingAsJwt($user)->putJson(self::URI, ['notification_preferences' => ['admin_messages' => false]])
            ->assertOk()
            ->assertJsonPath('notificationPreferences.absenceAlert', true)
            ->assertJsonPath('notificationPreferences.adminMessages', false);
    }

    public function test_put_without_payload_resets_everything_to_enabled(): void
    {
        $user = $this->makeUser('member');
        $this->actingAsJwt($user)->putJson(self::URI, ['notification_preferences' => ['participant_upcoming' => false]])->assertOk();

        $this->actingAsJwt($user)->putJson(self::URI, [])
            ->assertOk()->assertExactJson(['status' => 'ok', 'notificationPreferences' => self::DEFAULTS]);
    }

    public function test_put_ignores_camel_case_request_keys(): void
    {
        $user = $this->makeUser('member');

        $this->actingAsJwt($user)->putJson(self::URI, ['notificationPreferences' => ['absence_alert' => false]])
            ->assertOk()->assertJsonPath('notificationPreferences.absenceAlert', true);
    }

    public function test_put_does_not_validate_unknown_types_or_values(): void
    {
        // characterization: there is no validation, unknown keys and non-boolean values are stored as sent
        $user = $this->makeUser('member');

        $this->actingAsJwt($user)->putJson(self::URI, ['notification_preferences' => ['foo' => 'bar', 'absence_alert' => 'nope']])
            ->assertOk()
            ->assertJsonPath('notificationPreferences.foo', 'bar')
            ->assertJsonPath('notificationPreferences.absenceAlert', 'nope');
    }

    public function test_put_with_a_non_array_payload_is_a_500(): void
    {
        // characterization: NotificationPreferenceService::updatePreferences(array) throws a TypeError
        $this->actingAsJwt($this->makeUser('member'))->putJson(self::URI, ['notification_preferences' => 'x'])->assertStatus(500);
    }

    public function test_preferences_are_per_user(): void
    {
        $a = $this->makeUser('member');
        $b = $this->makeUser('member');

        $this->actingAsJwt($a)->putJson(self::URI, ['notification_preferences' => ['trainer_upcoming' => false]])->assertOk();

        $this->actingAsJwt($b)->getJson(self::URI)->assertJsonPath('notificationPreferences.trainerUpcoming', true);
    }
}
