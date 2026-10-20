<?php

namespace Tests\Feature;

use App\Branch;
use App\Group;
use App\Location;
use Database\Factories\BranchFactory;
use Database\Factories\GroupFactory;
use Database\Factories\LocationFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

/**
 * Groups: trainers may create/update/delete. Locations and branches: only admins
 * (the permissions create-location/update-location/... are not part of any seeded role,
 * so trainers are rejected and admins pass through Gate::before).
 */
class MasterDataCrudTest extends TestCase
{
    public function test_trainer_creates_a_group_with_members(): void
    {
        $trainer = $this->makeUser('trainer');
        $branch = BranchFactory::new()->create();
        $u1 = UserFactory::new()->create();
        $u2 = UserFactory::new()->create();

        $this->actingAsUser($trainer)->postJson('/api/v1/group', [
            'name' => 'Kids', 'branchId' => $branch->id, 'userIds' => [$u1->id, $u2->id],
        ])->assertStatus(201)->assertExactJson(['status' => 'ok']);

        $group = Group::firstOrFail();
        $this->assertSame('Kids', $group->name);
        $this->assertSame($branch->id, $group->branch_id);
        $this->assertEqualsCanonicalizing([$u1->id, $u2->id], $group->users()->pluck('users.id')->all());
    }

    public function test_group_validation(): void
    {
        $trainer = $this->makeUser('trainer');

        $this->actingAsUser($trainer)->postJson('/api/v1/group', [])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'branchId']);
    }

    public function test_trainer_updates_a_group_and_resyncs_members(): void
    {
        $trainer = $this->makeUser('trainer');
        $branch = BranchFactory::new()->create();
        $group = GroupFactory::new()->create();
        $old = UserFactory::new()->inGroups([$group])->create();
        $new = UserFactory::new()->create();

        $this->actingAsUser($trainer)->putJson("/api/v1/group/{$group->id}", [
            'name' => 'Teens', 'branchId' => $branch->id, 'userIds' => [$new->id],
        ])->assertOk()->assertExactJson(['status' => 'ok']);

        $group->refresh();
        $this->assertSame('Teens', $group->name);
        $this->assertSame([$new->id], $group->users()->pluck('users.id')->all());
        $this->assertNotContains($old->id, $group->users()->pluck('users.id')->all());
    }

    public function test_trainer_deletes_a_group(): void
    {
        $trainer = $this->makeUser('trainer');
        $group = GroupFactory::new()->create();

        $this->actingAsUser($trainer)->deleteJson("/api/v1/group/{$group->id}")->assertOk()->assertExactJson(['status' => 'ok']);

        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
        $this->actingAsUser($trainer)->deleteJson('/api/v1/group/999999')->assertNotFound();
    }

    public function test_admin_creates_and_updates_a_location(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsUser($admin)->postJson('/api/v1/location', ['name' => 'Halle 1'])
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);
        $location = Location::firstOrFail();
        $this->assertSame('Halle 1', $location->name);

        $this->actingAsUser($admin)->putJson("/api/v1/location/{$location->id}", ['name' => 'Halle 2'])
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);
        $this->assertSame('Halle 2', $location->fresh()->name);
    }

    public function test_location_validation_and_unknown_id(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsUser($admin)->postJson('/api/v1/location', [])->assertStatus(422)->assertJsonValidationErrors(['name']);
        $this->actingAsUser($admin)->putJson('/api/v1/location/999999', ['name' => 'x'])->assertNotFound();
    }

    public function test_admin_creates_and_updates_a_branch(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsUser($admin)->postJson('/api/v1/branch', ['name' => 'Judo', 'shortName' => 'JUD', 'colorHex' => '#112233'])
            ->assertStatus(201)->assertExactJson(['status' => 'ok']);
        $branch = Branch::firstOrFail();
        $this->assertSame('JUD', $branch->short_name);
        $this->assertSame('#112233', $branch->colorHex);

        $this->actingAsUser($admin)->putJson("/api/v1/branch/{$branch->id}", ['name' => 'Karate', 'shortName' => 'KAR', 'colorHex' => '#445566'])
            ->assertStatus(201);
        $this->assertSame('Karate', $branch->fresh()->name);
    }

    public function test_branch_validation(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAsUser($admin)->postJson('/api/v1/branch', [])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'shortName']);
    }

    public function test_trainer_cannot_manage_locations_or_branches(): void
    {
        $trainer = $this->makeUser('trainer');
        $location = LocationFactory::new()->create();
        $branch = BranchFactory::new()->create();

        $this->actingAsUser($trainer)->postJson('/api/v1/location', ['name' => 'x'])->assertForbidden();
        $this->actingAsUser($trainer)->putJson("/api/v1/location/{$location->id}", ['name' => 'x'])->assertForbidden();
        $this->actingAsUser($trainer)->postJson('/api/v1/branch', ['name' => 'x', 'shortName' => 'X'])->assertForbidden();
        $this->actingAsUser($trainer)->putJson("/api/v1/branch/{$branch->id}", ['name' => 'x', 'shortName' => 'X'])->assertForbidden();
    }
}
