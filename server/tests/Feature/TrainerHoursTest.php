<?php

namespace Tests\Feature;

use App\Exports\TrainingTrainerExport;
use App\TrainingTrainer;
use Carbon\Carbon;
use Database\Factories\GroupFactory;
use Database\Factories\TrainingFactory;
use Tests\TestCase;

class TrainerHoursTest extends TestCase
{
    private function trained($trainer, string $start, string $end, array $attrs = [], $group = null)
    {
        $t = TrainingFactory::new()->between($start, $end)->forGroups([$group ?? GroupFactory::new()->create()])->withTrainers([$trainer])
            ->create(array_merge(['evaluated' => 1], $attrs));
        TrainingTrainer::where('training_id', $t->id)->where('user_id', $trainer->id)->update(['accounting_time_start' => $start, 'accounting_time_end' => $end]);

        return $t;
    }

    private function hours($actor, int $year = 2030)
    {
        return $this->actingAsUser($actor)->getJson("/api/v1/user/me/hours?year=$year");
    }

    public function test_requires_a_login_and_a_trainer_or_admin(): void
    {
        $this->getJson('/api/v1/user/me/hours')->assertStatus(401);
        $this->hours($this->makeUser('member'))->assertStatus(403);
        $this->hours($this->makeUser('trainer'))->assertOk();
        $this->hours($this->makeUser('admin'))->assertOk();
    }

    public function test_sums_by_month_and_quarter_and_lists_the_entries(): void
    {
        $trainer = $this->makeUser('trainer');
        $group = GroupFactory::new()->create(['name' => 'Kinder']);
        $this->trained($trainer, '2030-01-07 18:00:00', '2030-01-07 19:30:00', [], $group);   // 90 min
        $this->trained($trainer, '2030-01-14 18:00:00', '2030-01-14 19:00:00', [], $group);   // 60 min
        $this->trained($trainer, '2030-03-04 17:00:00', '2030-03-04 18:30:00');               // 90 min
        $this->trained($trainer, '2030-04-01 17:00:00', '2030-04-01 17:45:00');               // 45 min = 1 h
        $this->trained($trainer, '2029-12-31 17:00:00', '2029-12-31 18:30:00');               // other year
        $this->trained($trainer, '2030-12-31 22:00:00', '2030-12-31 23:30:00');               // last day of the year

        $data = $this->hours($trainer)->assertOk()->json();

        $this->assertSame(2030, $data['year']);
        $this->assertSame(5, $data['trainingCount']);
        $this->assertSame(90 + 60 + 90 + 45 + 90, $data['minutes']);
        $byMonth = array_column($data['months'], null, 'month');
        $this->assertSame(2, $byMonth[1]['trainingCount']);
        $this->assertSame(150, $byMonth[1]['minutes']);
        $this->assertSame(3.33, $byMonth[1]['hours'], '2 + 1.33 (each entry rounded as in the export)');
        $this->assertSame(['Kinder'], $byMonth[1]['entries'][0]['groups']);
        $this->assertSame('2030-01-07T18:00:00', $byMonth[1]['entries'][0]['start']);
        $this->assertSame('2030-01-07T19:30:00', $byMonth[1]['entries'][0]['end']);
        $this->assertSame(0, $byMonth[2]['trainingCount']);
        $this->assertSame(5, count($data['months']) - 7);
        $quarters = array_column($data['quarters'], null, 'quarter');
        $this->assertSame([3, 1, 0, 1], [$quarters[1]['trainingCount'], $quarters[2]['trainingCount'], $quarters[3]['trainingCount'], $quarters[4]['trainingCount']]);
        $this->assertSame(240, $quarters[1]['minutes']);
        $this->assertEqualsWithDelta(array_sum(array_column($data['quarters'], 'hours')), $data['hours'], 0.001);
    }

    public function test_only_own_data_deleted_trainings_excluded_and_unevaluated_listed_separately(): void
    {
        $me = $this->makeUser('trainer');
        $other = $this->makeUser('trainer');
        $this->trained($me, '2030-02-04 18:00:00', '2030-02-04 19:30:00');
        $this->trained($other, '2030-02-05 18:00:00', '2030-02-05 19:30:00');
        $deleted = $this->trained($me, '2030-02-06 18:00:00', '2030-02-06 19:30:00');
        $deleted->delete();
        $past = now()->subDays(3)->format('Y-m-d');
        $open = TrainingFactory::new()->between("$past 18:00:00", "$past 19:30:00")->forGroups([GroupFactory::new()->create(['name' => 'Offen'])])->withTrainers([$me])->create(['evaluated' => 0]);
        $future = TrainingFactory::new()->between(now()->addDay()->format('Y-m-d').' 18:00:00', now()->addDay()->format('Y-m-d').' 19:00:00')->withTrainers([$me])->create(['evaluated' => 0]);
        TrainingFactory::new()->between("$past 20:00:00", "$past 21:00:00")->withTrainers([$other])->create(['evaluated' => 0]);

        $data = $this->hours($me)->assertOk()->json();

        $this->assertSame(1, $data['trainingCount']);
        $this->assertSame(90, $data['minutes']);
        $this->assertSame([$open->id], array_column($data['unevaluated'], 'id'), 'own, past, not evaluated only');
        $this->assertSame(['Offen'], $data['unevaluated'][0]['groups']);
        $this->assertNotContains($future->id, array_column($data['unevaluated'], 'id'));
    }

    public function test_the_numbers_match_the_accounting_export_exactly(): void
    {
        $trainer = $this->makeUser('trainer');
        $this->trained($trainer, '2030-05-02 18:00:00', '2030-05-02 19:20:00'); // 80 min = 1.78 h
        $this->trained($trainer, '2030-05-09 18:00:00', '2030-05-09 19:50:00'); // 110 min = 2.44 h
        $this->trained($trainer, '2030-06-13 09:00:00', '2030-06-13 12:00:00'); // 180 min = 4 h
        $deleted = $this->trained($trainer, '2030-06-20 09:00:00', '2030-06-20 12:00:00');
        $deleted->delete();

        $export = new TrainingTrainerExport($trainer, Carbon::parse('2030-01-01 00:00:00'), Carbon::parse('2030-12-31 23:59:59'));
        $rows = $export->collection()->map(fn ($e) => $export->map($e));
        $exportHours = round($rows->sum(fn ($row) => $row[5]), 2);
        $exportMinutes = (int) $rows->sum(fn ($row) => $row[6]);

        $data = $this->hours($trainer)->json();

        $this->assertCount(3, $rows, 'the export leaves out the deleted training, too');
        $this->assertSame($exportHours, $data['hours']);
        $this->assertSame($exportMinutes, $data['minutes']);
        $this->assertSame(8.22, $data['hours']);
    }

    public function test_validates_the_year(): void
    {
        $trainer = $this->makeUser('trainer');

        $this->hours($trainer, 1800)->assertStatus(422);
        $this->actingAsUser($trainer)->getJson('/api/v1/user/me/hours?year=abc')->assertStatus(422);
    }
}
