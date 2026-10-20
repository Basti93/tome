<?php

namespace Tests\Feature\PublicApi;

use App\NotificationToken;
use Database\Factories\BranchFactory;
use Database\Factories\ContentFactory;
use Database\Factories\GroupFactory;
use Database\Factories\LocationFactory;
use Database\Factories\TrainingSeriesFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Public (unauthenticated) master data endpoints and the exact JSON they expose.
 */
class MasterDataTest extends TestCase
{
    public function test_simple_users_expose_only_minimal_data_without_login(): void
    {
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['branch_id' => $branch->id]);
        $user = UserFactory::new()->inGroups([$group])->trainerOfBranches([$branch])->create([
            'firstName' => 'Anna', 'familyName' => 'Alt', 'birthdate' => '1990-05-05', 'profile_image_name' => 'profile_images/a.jpg',
        ]);
        UserFactory::new()->inactive()->create();

        // anonymous: "Vorname M." and no photo
        $this->getJson('/api/v1/simpleuser')
            ->assertOk()
            ->assertExactJson(['data' => [[
                'id' => $user->id,
                'firstName' => 'Anna',
                'familyName' => 'A.',
                'groupIds' => [$group->id],
                'profileImageName' => null,
                'trainerBranchIds' => [$branch->id],
                'active' => 1,
            ]]]);
    }

    public function test_simple_users_expose_full_names_and_photos_to_logged_in_users(): void
    {
        $user = UserFactory::new()->create(['firstName' => 'Anna', 'familyName' => 'Alt', 'profile_image_name' => 'profile_images/a.jpg']);

        $response = $this->actingAsUser($this->makeUser('member'))->getJson('/api/v1/simpleuser')->assertOk();

        $row = collect($response->json('data'))->firstWhere('id', $user->id);
        $this->assertSame('Alt', $row['familyName']);
        $this->assertSame('profile_images/a.jpg', $row['profileImageName']);
    }

    public function test_simple_trainers_expose_full_names_with_only_the_session_like_the_browser(): void
    {
        $trainer = $this->makeUser('trainer', ['firstName' => 'Tina', 'familyName' => 'Trainer']);

        $anonymous = $this->getJson('/api/v1/simpleuser/trainers')->assertOk();
        $this->assertSame('T.', collect($anonymous->json('data'))->firstWhere('id', $trainer->id)['familyName']);

        // the HttpOnly cookie is the ONLY credential (no Authorization header)
        $loggedIn = $this->actingAsUser($this->makeUser('member'))->getJson('/api/v1/simpleuser/trainers')->assertOk();
        $this->assertSame('Trainer', collect($loggedIn->json('data'))->firstWhere('id', $trainer->id)['familyName']);
    }

    public function test_simple_user_initial_handles_empty_and_multibyte_names(): void
    {
        $empty = UserFactory::new()->create(['familyName' => '']);
        $umlaut = UserFactory::new()->create(['familyName' => 'ünal']);

        $rows = collect($this->getJson('/api/v1/simpleuser')->assertOk()->json('data'))->keyBy('id');

        $this->assertSame('', $rows[$empty->id]['familyName']);
        $this->assertSame('Ü.', $rows[$umlaut->id]['familyName']);
    }

    public function test_a_garbage_session_cookie_is_treated_like_no_login(): void
    {
        UserFactory::new()->create(['familyName' => 'Alt']);

        $this->fromSpa()->withUnencryptedCookie(config('session.cookie'), 'garbage')->getJson('/api/v1/simpleuser')
            ->assertOk()
            ->assertJsonPath('data.0.familyName', 'A.');
    }

    public function test_simple_users_can_be_filtered_by_group_ids(): void
    {
        [$g1, $g2, $g3] = [GroupFactory::new()->create(), GroupFactory::new()->create(), GroupFactory::new()->create()];
        $u1 = UserFactory::new()->inGroups([$g1])->create();
        $u2 = UserFactory::new()->inGroups([$g2])->create();
        UserFactory::new()->inGroups([$g3])->create();

        $ids = collect($this->getJson("/api/v1/simpleuser?groupIds={$g1->id},{$g2->id}")->assertOk()->json('data'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$u1->id, $u2->id], $ids);
    }

    public function test_simple_user_trainers_lists_users_with_trainer_role(): void
    {
        $trainer = $this->makeUser('trainer');
        $this->makeUser('member');
        $this->makeUser('admin');

        $response = $this->getJson('/api/v1/simpleuser/trainers')->assertOk();

        $this->assertSame([$trainer->id], collect($response->json('data'))->pluck('id')->all());
        $response->assertJsonStructure(['data' => [['id', 'firstName', 'familyName', 'groupIds', 'profileImageName', 'trainerBranchIds', 'active']]]);
        $this->assertNull($response->json('data.0.profileImageName'));
    }

    public function test_branches_are_paginated_and_sorted_by_name(): void
    {
        BranchFactory::new()->create(['name' => 'Zumba', 'short_name' => 'ZUM', 'colorHex' => '#ff0000']);
        $b = BranchFactory::new()->create(['name' => 'Aikido', 'short_name' => 'AIK', 'colorHex' => '#00ff00']);

        $response = $this->getJson('/api/v1/branch')->assertOk();

        $response->assertJsonPath('data.0', ['id' => $b->id, 'name' => 'Aikido', 'shortName' => 'AIK', 'colorHex' => '#00ff00']);
        $response->assertJsonPath('data.1.name', 'Zumba');
        $response->assertJsonStructure(['data', 'links' => ['first', 'last', 'prev', 'next'], 'meta' => ['currentPage', 'perPage', 'total']]);
        $response->assertJsonPath('meta.perPage', 10);
        $response->assertJsonPath('meta.total', 2);
    }

    public function test_branches_honor_per_page(): void
    {
        BranchFactory::new()->count(3)->create();

        $this->getJson('/api/v1/branch?per_page=2')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.lastPage', 2);
    }

    public function test_locations_are_paginated_and_sorted_by_name(): void
    {
        LocationFactory::new()->create(['name' => 'Turnhalle']);
        $l = LocationFactory::new()->create(['name' => 'Aula']);

        $this->getJson('/api/v1/location')->assertOk()
            ->assertJsonPath('data.0', ['id' => $l->id, 'name' => 'Aula'])
            ->assertJsonPath('data.1.name', 'Turnhalle');
    }

    public function test_groups_hide_their_member_ids_without_login(): void
    {
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['name' => 'Kids', 'branch_id' => $branch->id]);
        UserFactory::new()->inGroups([$group])->create();

        $this->getJson('/api/v1/group')->assertOk()
            ->assertJsonPath('data.0', [
                'id' => $group->id,
                'name' => 'Kids',
                'branchId' => $branch->id,
                'userIds' => [],
            ])
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_groups_expose_the_active_user_ids_to_logged_in_users(): void
    {
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['name' => 'Kids', 'branch_id' => $branch->id]);
        $active = UserFactory::new()->inGroups([$group])->create();
        UserFactory::new()->inactive()->inGroups([$group])->create();

        $this->actingAsUser($this->makeUser('member'))->getJson('/api/v1/group')->assertOk()
            ->assertJsonPath('data.0.userIds', [$active->id]);
    }

    public function test_groups_are_sorted_by_branch_then_name(): void
    {
        $b1 = BranchFactory::new()->create();
        $b2 = BranchFactory::new()->create();
        GroupFactory::new()->create(['name' => 'B', 'branch_id' => $b2->id]);
        GroupFactory::new()->create(['name' => 'Z', 'branch_id' => $b1->id]);
        GroupFactory::new()->create(['name' => 'A', 'branch_id' => $b1->id]);

        $names = collect($this->getJson('/api/v1/group')->json('data'))->pluck('name')->all();

        $this->assertSame(['A', 'Z', 'B'], $names);
    }

    public function test_contents_are_returned_as_plain_json_sorted_by_branch_and_order(): void
    {
        $branch = BranchFactory::new()->create();
        $noOrder = ContentFactory::new()->create(['name' => 'no order', 'branch_id' => $branch->id, 'order' => null]);
        $second = ContentFactory::new()->create(['name' => 'second', 'branch_id' => $branch->id, 'order' => 2]);
        $first = ContentFactory::new()->create(['name' => 'first', 'branch_id' => $branch->id, 'order' => 1, 'image_name' => 'x.png']);

        $response = $this->getJson('/api/v1/content')->assertOk();

        $this->assertSame([$first->id, $second->id, $noOrder->id], collect($response->json())->pluck('id')->all());
        $response->assertJsonPath('0', [
            'id' => $first->id,
            'name' => 'first',
            'branchId' => $branch->id,
            'order' => 1,
            'imageName' => 'x.png',
        ]);
    }

    public function test_training_series_are_listed_with_resource_shape(): void
    {
        $group = GroupFactory::new()->create();
        $trainer = UserFactory::new()->create();
        $location = LocationFactory::new()->create();
        $series = TrainingSeriesFactory::new()
            ->onWeekdays([2, 4])
            ->forGroups([$group])
            ->withTrainers([$trainer])
            ->create(['startTime' => '18:00:00', 'endTime' => '19:30:00', 'comment' => 'Hallo', 'location_id' => $location->id, 'automatic_attend' => 1]);

        $this->getJson('/api/v1/trainingSeries')->assertOk()
            ->assertJsonPath('data.0', [
                'id' => $series->id,
                'startTime' => '18:00',
                'endTime' => '19:30',
                'weekdays' => [2, 4],
                'locationId' => $location->id,
                'groupIds' => [$group->id],
                'trainerIds' => [$trainer->id],
                'contentIds' => [],
                'comment' => 'Hallo',
                'deferUntil' => null,
                'automaticAttend' => 1,
            ])
            ->assertJsonPath('meta.perPage', 100);
    }

    public function test_training_series_can_be_filtered_by_group(): void
    {
        $g1 = GroupFactory::new()->create();
        $g2 = GroupFactory::new()->create();
        $s1 = TrainingSeriesFactory::new()->forGroups([$g1])->create();
        TrainingSeriesFactory::new()->forGroups([$g2])->create();

        $ids = collect($this->getJson("/api/v1/trainingSeries?groupIds={$g1->id}")->json('data'))->pluck('id')->all();

        $this->assertSame([$s1->id], $ids);
    }

    public function test_the_faq_feature_is_gone(): void
    {
        $this->getJson('/api/v1/faq')->assertNotFound();
        $this->getJson('/api/v1/faq/files')->assertNotFound();
        $this->assertFalse(Schema::hasTable('faqs'));
    }

    public function test_notification_subscribe_stores_the_token_and_reassigns_it(): void
    {
        $u1 = $this->makeUser('member');
        $u2 = $this->makeUser('member');

        $this->postJson('/api/v1/notifications/subscribe', ['userId' => $u1->id, 'firebaseToken' => 'tok-1'])
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);
        $this->assertDatabaseHas('user_notification_token', ['token' => 'tok-1', 'user_id' => $u1->id]);

        $this->postJson('/api/v1/notifications/subscribe', ['userId' => $u2->id, 'firebaseToken' => 'tok-1'])->assertStatus(201);
        $this->assertDatabaseCount('user_notification_token', 1);
        $this->assertDatabaseHas('user_notification_token', ['token' => 'tok-1', 'user_id' => $u2->id]);
    }

    public function test_resubscribing_the_same_token_refreshes_updated_at(): void
    {
        $user = $this->makeUser('member');
        $this->postJson('/api/v1/notifications/subscribe', ['userId' => $user->id, 'firebaseToken' => 'tok-2'])->assertStatus(201);
        DB::table('user_notification_token')->where('token', 'tok-2')->update(['updated_at' => now()->subMonths(11)]);

        $this->postJson('/api/v1/notifications/subscribe', ['userId' => $user->id, 'firebaseToken' => 'tok-2'])->assertStatus(201);

        // a token that is still in use must not look stale to tome:retention
        $this->assertTrue(NotificationToken::whereToken('tok-2')->first()->updated_at->isAfter(now()->subMinute()));
    }

    public function test_notification_subscribe_validates_input(): void
    {
        $this->postJson('/api/v1/notifications/subscribe', [])
            ->assertStatus(422)->assertJsonValidationErrors(['userId', 'firebaseToken']);
    }

    public function test_the_birthdate_is_only_sent_to_logged_in_trainers_and_admins(): void
    {
        $kid = UserFactory::new()->create(['firstName' => 'Mia', 'familyName' => 'Alt', 'birthdate' => '2015-10-20']);
        $row = fn ($response) => collect($response->json('data'))->firstWhere('id', $kid->id);

        $this->assertArrayNotHasKey('birthdate', $row($this->getJson('/api/v1/simpleuser')->assertOk()), 'anonymous');
        $this->assertArrayNotHasKey('birthdate', $row($this->getJson('/api/v1/simpleuser/trainers')->assertOk()) ?? [], 'anonymous trainers list');
        $this->assertArrayNotHasKey('birthdate', $row($this->actingAsUser($this->makeUser('member'))->getJson('/api/v1/simpleuser')->assertOk()), 'member');
        $this->assertSame('2015-10-20', $row($this->actingAsUser($this->makeUser('trainer'))->getJson('/api/v1/simpleuser')->assertOk())['birthdate']);
        $this->assertSame('2015-10-20', $row($this->actingAsUser($this->makeUser('admin'))->getJson('/api/v1/simpleuser')->assertOk())['birthdate']);
    }
}
