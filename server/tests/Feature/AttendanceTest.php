<?php

namespace Tests\Feature;

use Database\Factories\BranchFactory;
use Database\Factories\GroupFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    private function training($group, string $day, array $attrs = [])
    {
        return TrainingFactory::new()->between("$day 18:00:00", "$day 19:30:00")->forGroups([$group])->create(array_merge(['evaluated' => 1], $attrs));
    }

    private function attendance(array $query, $actor)
    {
        return $this->actingAsUser($actor)->getJson('/api/v1/attendance?'.http_build_query($query));
    }

    public function test_requires_a_login(): void
    {
        $this->getJson('/api/v1/attendance?groupId=1&from=2030-01-01&to=2030-03-31')->assertStatus(401);
    }

    public function test_only_trainers_of_the_groups_branch_and_admins_may_view_it(): void
    {
        $branch = BranchFactory::new()->create();
        $other = BranchFactory::new()->create();
        $group = GroupFactory::new()->create(['branch_id' => $branch->id]);
        $query = ['groupId' => $group->id, 'from' => '2030-01-01', 'to' => '2030-03-31'];

        $this->attendance($query, $this->makeUser('member'))->assertStatus(403);
        $this->attendance($query, UserFactory::new()->role('trainer')->trainerOfBranches([$other])->create())->assertStatus(403);
        $this->attendance($query, UserFactory::new()->role('trainer')->trainerOfBranches([$branch])->create())->assertOk();
        $this->attendance($query, $this->makeUser('admin'))->assertOk();
    }

    public function test_validates_the_query(): void
    {
        $admin = $this->makeUser('admin');

        $this->attendance(['groupId' => 1], $admin)->assertStatus(422);
        $this->attendance(['groupId' => 1, 'from' => '2030-03-31', 'to' => '2030-01-01'], $admin)->assertStatus(422);
        $this->attendance(['groupId' => 99999, 'from' => '2030-01-01', 'to' => '2030-03-31'], $admin)->assertStatus(404);
    }

    public function test_matrix_rates_and_counts(): void
    {
        $group = GroupFactory::new()->create();
        $anna = UserFactory::new()->inGroups([$group])->create(['firstName' => 'Anna', 'familyName' => 'Alt']);
        $ben = UserFactory::new()->inGroups([$group])->create(['firstName' => 'Ben', 'familyName' => 'Bauer']);
        $inactive = UserFactory::new()->inactive()->inGroups([$group])->create();
        $outsider = UserFactory::new()->create();

        $t1 = $this->training($group, '2030-02-04');
        $t2 = $this->training($group, '2030-02-11');
        $t3 = $this->training($group, '2030-02-18');
        $t4 = $this->training($group, '2030-02-25');
        $t1->participants()->attach($anna->id, ['attend' => 1]);
        $t2->participants()->attach($anna->id, ['attend' => 1]);
        $t3->participants()->attach($anna->id, ['attend' => 0, 'cancelreason' => 'Krank']);
        $t1->participants()->attach($ben->id, ['attend' => 1]);
        $t2->participants()->attach($ben->id, ['attend' => 0]);
        $t2->participants()->attach($outsider->id, ['attend' => 1]);
        $t1->participants()->attach($inactive->id, ['attend' => 1]);

        $data = $this->attendance(['groupId' => $group->id, 'from' => '2030-02-01', 'to' => '2030-02-28'], $this->makeUser('admin'))->assertOk()->json();

        $this->assertSame([$t1->id, $t2->id, $t3->id, $t4->id], array_column($data['trainings'], 'id'));
        $this->assertSame([2, 1, 0, 0], array_column($data['trainings'], 'presentCount'), 'present athletes per training (members only)');
        $this->assertSame(['Alt', 'Bauer'], array_column($data['athletes'], 'familyName'), 'current active members only, by name');

        [$a, $b] = $data['athletes'];
        $this->assertSame(2, $a['presentCount']);
        $this->assertSame(50, $a['rate'], '2 of the 4 evaluated trainings of the group');
        $this->assertSame(['present', 'present', 'excused', 'none'], array_column($a['cells'], 'status'));
        $this->assertSame('Krank', $a['cells'][2]['reason']);
        $this->assertSame(25, $b['rate']);
        $this->assertSame(['present', 'absent', 'none', 'none'], array_column($b['cells'], 'status'));
        $this->assertNull($b['cells'][1]['reason']);
    }

    public function test_only_evaluated_not_deleted_trainings_in_the_range_of_the_group_count(): void
    {
        $group = GroupFactory::new()->create();
        $otherGroup = GroupFactory::new()->create();
        $anna = UserFactory::new()->inGroups([$group])->create();

        $counted = $this->training($group, '2030-02-04');
        $this->training($group, '2030-02-11', ['evaluated' => 0]);
        $deleted = $this->training($group, '2030-02-18');
        $deleted->delete();
        $this->training($group, '2029-12-31');
        $this->training($group, '2030-03-01');
        $this->training($otherGroup, '2030-02-05');
        $lastDay = $this->training($group, '2030-02-28');
        foreach ([$counted, $lastDay] as $t) {
            $t->participants()->attach($anna->id, ['attend' => 1]);
        }

        $data = $this->attendance(['groupId' => $group->id, 'from' => '2030-02-01', 'to' => '2030-02-28'], $this->makeUser('admin'))->assertOk()->json();

        $this->assertSame([$counted->id, $lastDay->id], array_column($data['trainings'], 'id'), 'the last day of the range is included');
        $this->assertSame(100, $data['athletes'][0]['rate']);
    }

    public function test_without_trainings_the_rate_is_null(): void
    {
        $group = GroupFactory::new()->create();
        UserFactory::new()->inGroups([$group])->create();

        $data = $this->attendance(['groupId' => $group->id, 'from' => '2030-02-01', 'to' => '2030-02-28'], $this->makeUser('admin'))->assertOk()->json();

        $this->assertSame([], $data['trainings']);
        $this->assertNull($data['athletes'][0]['rate']);
    }

    public function test_the_number_of_queries_does_not_grow_with_the_group_size(): void
    {
        $group = GroupFactory::new()->create();
        $t = $this->training($group, '2030-02-04');
        $this->training($group, '2030-02-11');
        $admin = $this->makeUser('admin');
        $count = function () use ($group, $admin) {
            \DB::flushQueryLog();
            \DB::enableQueryLog();
            $this->attendance(['groupId' => $group->id, 'from' => '2030-02-01', 'to' => '2030-02-28'], $admin)->assertOk();

            return count(\DB::getQueryLog());
        };
        $small = $count();
        foreach (UserFactory::new()->count(15)->inGroups([$group])->create() as $u) {
            $t->participants()->attach($u->id, ['attend' => 1]);
        }

        $this->assertSame($small, $count());
    }
}
