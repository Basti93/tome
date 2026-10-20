<?php

namespace Tests\Feature;

use App\Services\NotificationPreferenceService;
use Tests\TestCase;

/**
 * GET|PUT /api/v1/user/me/notification-preferences. Request keys are snake_case
 * (`notification_preferences`, `absence_alert`), response keys are camelCased by the JSON middleware.
 */
class NotificationPreferencesTest extends TestCase
{
    private const URI = '/api/v1/user/me/notification-preferences';

    private const DEFAULTS = [
        'trainerUpcoming' => false,
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
            ->assertJsonPath('notificationPreferences.trainerCancelAlert', true);
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

    public function test_put_drops_unknown_types_and_casts_booleans(): void
    {
        $user = $this->makeUser('member');

        $this->actingAsJwt($user)->putJson(self::URI, ['notification_preferences' => [
            'foo' => ['nested' => str_repeat('x', 1000)], 'absence_alert' => 0, 'admin_messages' => '1', 'trainer_upcoming' => false,
        ]])
            ->assertOk()
            ->assertJsonMissingPath('notificationPreferences.foo')
            ->assertJsonPath('notificationPreferences.absenceAlert', false)
            ->assertJsonPath('notificationPreferences.adminMessages', true)
            ->assertJsonPath('notificationPreferences.trainerUpcoming', false);

        $stored = $user->fresh()->notification_preferences;
        $expected = array_keys((new NotificationPreferenceService)->getDefaultPreferences());
        $actual = array_keys($stored);
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
        $this->assertSame([], array_filter($stored, fn ($v) => ! is_bool($v)));
    }

    public function test_put_rejects_non_boolean_values_and_non_array_payloads_with_422(): void
    {
        $user = $this->makeUser('member');

        $this->actingAsJwt($user)->putJson(self::URI, ['notification_preferences' => ['absence_alert' => 'nope']])
            ->assertStatus(422)->assertJsonValidationErrors(['notificationPreferences.absenceAlert']);
        $this->actingAsJwt($user)->putJson(self::URI, ['notification_preferences' => ['absence_alert' => ['x']]])->assertStatus(422);
        $this->actingAsJwt($user)->putJson(self::URI, ['notification_preferences' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors(['notificationPreferences']);
        $this->assertNull($user->fresh()->notification_preferences);
    }

    public function test_preferences_are_per_user(): void
    {
        $a = $this->makeUser('member');
        $b = $this->makeUser('member');

        $this->actingAsJwt($a)->putJson(self::URI, ['notification_preferences' => ['trainer_cancel_alert' => false]])->assertOk();

        $this->actingAsJwt($b)->getJson(self::URI)->assertJsonPath('notificationPreferences.trainerCancelAlert', true);
    }
}
