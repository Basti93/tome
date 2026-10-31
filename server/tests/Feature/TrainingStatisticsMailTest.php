<?php

namespace Tests\Feature;

use App\Group;
use App\Mail\UserTrainingStatistics;
use App\Training;
use App\TrainingTrainer;
use App\User;
use Database\Factories\BranchFactory;
use Database\Factories\GroupFactory;
use Database\Factories\TrainingFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TrainingStatisticsMailTest extends TestCase
{
    private $branch;

    private Group $group;

    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-01 00:00:00');
        $this->branch = BranchFactory::new()->create();
        $this->group = GroupFactory::new()->create(['branch_id' => $this->branch->id, 'name' => 'Gruppe Eins']);
        $this->trainer = UserFactory::new()->role('trainer')->trainerOfBranches([$this->branch])->create(['firstName' => 'Tina', 'familyName' => 'Trainer']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function member(string $first, string $family, array $attrs = []): User
    {
        return UserFactory::new()->inGroups([$this->group])->create(array_merge(['firstName' => $first, 'familyName' => $family], $attrs));
    }

    /** @param  array<int, User|int>  $attending */
    private function training(string $day, array $attending = [], array $absent = [], array $attrs = [], ?Group $group = null): Training
    {
        $t = TrainingFactory::new()->between("$day 18:00:00", "$day 19:30:00")->forGroups([$group ?? $this->group])
            ->create(array_merge(['evaluated' => 1], $attrs));
        foreach ($attending as $u) {
            $t->participants()->attach($u, ['attend' => 1]);
        }
        foreach ($absent as $u) {
            $t->participants()->attach($u, ['attend' => 0]);
        }

        return $t;
    }

    private function send(string $from = '2026-10-01', string $until = '2026-10-31'): UserTrainingStatistics
    {
        $this->artisan('emails:training-attendance-statistics', ['branchId' => $this->branch->id, 'startDate' => $from, 'endDate' => $until])->assertSuccessful();
        $sent = Mail::sent(UserTrainingStatistics::class);
        $this->assertCount(1, $sent);

        return $sent->first();
    }

    private function athlete(UserTrainingStatistics $mail, string $name, int $group = 0): array
    {
        return collect($mail->report['groups'][$group]['athletes'])->firstWhere('name', $name);
    }

    public function test_quotes_for_month_and_year_and_sorting(): void
    {
        $anna = $this->member('Anna', 'Alt');
        $ben = $this->member('Ben', 'Bauer');
        $this->training('2026-02-10', [$anna, $ben]);
        $this->training('2026-09-15', [$anna]);
        $this->training('2026-10-05', [$anna, $ben]);
        $this->training('2026-10-12', [$anna], [$ben]);
        $this->training('2026-10-19', [$anna]);
        $this->training('2025-12-01', [$ben]); // other year, not in the year
        $this->training('2026-10-20', [$ben], [], ['evaluated' => 0]); // not evaluated: not counted

        $mail = $this->send();
        $this->assertSame('Monatliche Trainingsstatistik 10.2026', $mail->build()->subject);

        $a = $this->athlete($mail, 'Anna Alt');
        $this->assertSame([3, 100, 5, 100], [$a['monthCount'], $a['monthQuote'], $a['yearCount'], $a['yearQuote']]);
        $b = $this->athlete($mail, 'Ben Bauer');
        $this->assertSame([1, 33, 2, 40], [$b['monthCount'], $b['monthQuote'], $b['yearCount'], $b['yearQuote']]);
        $this->assertSame(['Anna Alt', 'Ben Bauer'], array_column($mail->report['groups'][0]['athletes'], 'name'));
        $this->assertSame('19.10.2026', $a['lastAttendance']);

        $s = $mail->report['groups'][0]['summary'];
        $this->assertSame(3, $s['monthTrainings']);
        $this->assertSame(1.3, $s['avgParticipants']);
        $this->assertSame(67, $s['avgMonthQuote']);
        $this->assertSame(70, $s['avgYearQuote']);
        $this->assertSame(1, $s['cancellations']);
        $this->assertSame(1, $s['notEvaluated']);
    }

    public function test_group_summary_only_counts_members_of_the_group(): void
    {
        $anna = $this->member('Anna', 'Alt');
        $otherGroup = GroupFactory::new()->create(['branch_id' => $this->branch->id, 'name' => 'Gruppe Zwei']);
        $guest = UserFactory::new()->inGroups([$otherGroup])->create();
        $guest2 = UserFactory::new()->inGroups([$otherGroup])->create();

        $training = $this->training('2026-10-05', [$anna, $guest], [$guest2]);
        $training->groups()->attach($otherGroup);

        $summaries = collect($this->send()->report['groups'])->keyBy('name');
        $this->assertSame(1.0, $summaries['Gruppe Eins']['summary']['avgParticipants']);
        $this->assertSame(0, $summaries['Gruppe Eins']['summary']['cancellations']);
        $this->assertSame(1.0, $summaries['Gruppe Zwei']['summary']['avgParticipants']);
        $this->assertSame(1, $summaries['Gruppe Zwei']['summary']['cancellations']);
    }

    public function test_trend_compares_with_previous_month(): void
    {
        $up = $this->member('Up', 'Up');
        $down = $this->member('Down', 'Down');
        $same = $this->member('Same', 'Same');
        $none = $this->member('None', 'None');
        $this->training('2026-09-01', [$down, $same]);
        $this->training('2026-09-08', [$down, $same]);
        $this->training('2026-10-01', [$up, $same]);
        $this->training('2026-10-08', [$up, $same]);

        $mail = $this->send();
        $this->assertSame(1, $this->athlete($mail, 'Up Up')['trend']); // 0 % -> 100 %
        $this->assertSame(-1, $this->athlete($mail, 'Down Down')['trend']);
        $this->assertSame(0, $this->athlete($mail, 'Same Same')['trend']);
        $this->assertSame(0, $this->athlete($mail, 'None None')['trend']);
        $html = $mail->render();
        $this->assertStringContainsString('▲', $html);
        $this->assertStringContainsString('▼', $html);
    }

    public function test_trend_in_january_uses_december_of_previous_year(): void
    {
        $anna = $this->member('Anna', 'Alt');
        $this->training('2026-12-03', [$anna]);
        $this->training('2026-12-10', []);
        $this->training('2027-01-07', [$anna]);
        $this->training('2027-01-14', [$anna]);

        $mail = $this->send('2027-01-01', '2027-01-31');
        $a = $this->athlete($mail, 'Anna Alt');
        $this->assertSame(100, $a['monthQuote']);
        $this->assertSame(100, $a['yearQuote']); // the year only starts on 1 Jan
        $this->assertSame(1, $a['trend']); // December: 50 %
    }

    public function test_trainings_on_first_and_last_day_of_the_month_are_counted(): void
    {
        $anna = $this->member('Anna', 'Alt');
        $this->training('2026-10-01', [$anna]);
        $this->training('2026-10-31', [$anna]);
        $this->training('2026-11-01', [$anna]);
        TrainingFactory::new()->between('2026-10-31 23:30:00', '2026-10-31 23:59:00')->forGroups([$this->group])->create(['evaluated' => 1]);

        $mail = $this->send();
        $this->assertSame(3, $mail->report['groups'][0]['summary']['monthTrainings']);
        $this->assertSame(2, $this->athlete($mail, 'Anna Alt')['monthCount']);
    }

    public function test_deleted_trainings_and_other_groups_are_not_counted(): void
    {
        $anna = $this->member('Anna', 'Alt');
        $other = GroupFactory::new()->create(['branch_id' => $this->branch->id]);
        $this->training('2026-10-05', [$anna]);
        $this->training('2026-10-06', [$anna])->delete();
        $this->training('2026-10-07', [$anna], [], [], $other);

        $mail = $this->send();
        $this->assertSame(1, $this->athlete($mail, 'Anna Alt')['monthCount']);
        $this->assertSame(1, $mail->report['groups'][0]['summary']['monthTrainings']);
        $this->assertCount(1, $mail->report['groups']); // group without active members is skipped
    }

    public function test_not_evaluated_trainings_show_a_hint(): void
    {
        $this->member('Anna', 'Alt');
        $this->training('2026-10-05');
        $clean = $this->send()->render();
        $this->assertStringNotContainsString('unvollständig', $clean);

        Mail::fake();
        $this->training('2026-10-12', [], [], ['evaluated' => 0]);
        $mail = $this->send();
        $this->assertSame(1, $mail->report['groups'][0]['summary']['notEvaluated']);
        $this->assertStringContainsString('unvollständig', $mail->render());
    }

    public function test_group_without_trainings_shows_dashes(): void
    {
        $this->member('Anna', 'Alt');

        $mail = $this->send();
        $a = $this->athlete($mail, 'Anna Alt');
        $this->assertNull($a['monthQuote']);
        $this->assertNull($a['yearQuote']);
        $this->assertNull($a['trend']);
        $this->assertNull($a['lastAttendance']);
        $this->assertNull($mail->report['groups'][0]['summary']['avgMonthQuote']);
        $this->assertSame([], $mail->report['groups'][0]['watch']);
        $this->assertStringContainsString('–', $mail->render());
    }

    public function test_im_blick_list_and_absent_members(): void
    {
        $recent = $this->member('Rita', 'Recent');
        $long = $this->member('Lars', 'Lang');
        $never = $this->member('Nina', 'Nie');
        $absent = $this->member('Abi', 'Abwesend', ['absenceStart' => '2026-10-20', 'absenceEnd' => '2026-11-15', 'absenceReason' => 'Geheime Diagnose XYZ']);
        $this->training('2026-08-20', [$long, $absent]); // 72 days before the end of October
        $this->training('2026-10-20', [$recent]);

        $mail = $this->send();
        $watch = $mail->report['groups'][0]['watch'];
        $this->assertSame(['Nina Nie', 'Lars Lang'], array_column($watch, 'name')); // never attended first
        $this->assertNull($watch[0]['days']);
        $this->assertSame(72, $watch[1]['days']);

        $this->assertSame([['name' => 'Abi Abwesend', 'until' => '15.11.2026']], $mail->report['groups'][0]['absent']);
        $html = $mail->render();
        $this->assertStringContainsString('Im Blick behalten', $html);
        $this->assertStringContainsString('Aktuell abgemeldet', $html);
        $this->assertStringContainsString('noch nie dabei', $html);
        $this->assertStringNotContainsString('Geheime Diagnose', $html);
        $this->assertStringNotContainsString('absenceReason', json_encode($mail->report));
    }

    public function test_im_blick_requires_a_training_in_the_span(): void
    {
        $this->member('Lars', 'Lang');
        $this->training('2026-08-20'); // none in the last 28 days of October

        $this->assertSame([], $this->send()->report['groups'][0]['watch']);
    }

    public function test_birthdays_of_next_month_show_age_but_no_birth_year(): void
    {
        $this->member('Bea', 'Birth', ['birthdate' => '2012-11-09']);
        $this->member('Oli', 'Oct', ['birthdate' => '2010-10-09']);
        $this->training('2026-10-05');

        $mail = $this->send();
        $this->assertSame([['name' => 'Bea Birth', 'day' => 9, 'date' => '09.11.', 'age' => 14]], $mail->report['groups'][0]['birthdays']);
        $html = $mail->render();
        $this->assertStringContainsString('wird 14', $html);
        $this->assertStringNotContainsString('2012', $html);
    }

    public function test_numbers_are_computed_per_trainer_recipient(): void
    {
        $other = UserFactory::new()->role('trainer')->trainerOfBranches([$this->branch])->create(['firstName' => 'Otto', 'familyName' => 'Other']);
        $t1 = $this->training('2026-10-05')->trainers()->attach([$this->trainer->id, $other->id]);
        $t2 = $this->training('2026-10-12');
        $t2->trainers()->attach($this->trainer->id);
        $t3 = $this->training('2026-03-12');
        $t3->trainers()->attach($this->trainer->id);
        $t4 = $this->training('2026-10-19', [], [], ['evaluated' => 0]);
        $t4->trainers()->attach($this->trainer->id);
        TrainingTrainer::query()->where('training_id', $t2->id)->update(['accounting_time_start' => '2026-10-12 18:00:00', 'accounting_time_end' => '2026-10-12 19:30:00']);
        TrainingTrainer::query()->where('training_id', $t3->id)->update(['accounting_time_start' => '2026-03-12 18:00:00', 'accounting_time_end' => '2026-03-12 19:30:00']);
        $this->member('Anna', 'Alt');

        $this->artisan('emails:training-attendance-statistics', ['branchId' => $this->branch->id, 'startDate' => '2026-10-01', 'endDate' => '2026-10-31'])->assertSuccessful();
        Mail::assertSent(UserTrainingStatistics::class, 2);
        $mails = Mail::sent(UserTrainingStatistics::class);
        $mine = fn (User $u) => $mails->first(fn ($m) => $m->hasTo($u->email))->report['mine'];

        $this->assertSame(['monthCount' => 2, 'yearCount' => 3, 'monthHours' => 2.0, 'yearHours' => 4.0], $mine($this->trainer));
        $this->assertSame(['monthCount' => 1, 'yearCount' => 1, 'monthHours' => 0.0, 'yearHours' => 0.0], $mine($other));
        $html = $mails->first(fn ($m) => $m->hasTo($this->trainer->email))->render();
        $this->assertStringContainsString('Deine Einheiten', $html);
        $this->assertStringContainsString('Tina Trainer', $html);
    }

    public function test_values_are_escaped(): void
    {
        $this->member('<script>alert(1)</script>', 'Evil');
        $this->training('2026-10-05');

        $html = $this->send()->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_trainers_who_opted_out_get_no_mail_and_others_do(): void
    {
        $this->member('Anna', 'Alt');
        $out = UserFactory::new()->role('trainer')->trainerOfBranches([$this->branch])->create(['notification_preferences' => ['trainer_monthly_statistics' => false]]);
        $stored = UserFactory::new()->role('trainer')->trainerOfBranches([$this->branch])->create(['notification_preferences' => ['trainer_upcoming' => true]]);
        $on = UserFactory::new()->role('trainer')->trainerOfBranches([$this->branch])->create(['notification_preferences' => ['trainer_monthly_statistics' => true]]);

        $this->artisan('emails:training-attendance-statistics', ['branchId' => $this->branch->id, 'startDate' => '2026-10-01', 'endDate' => '2026-10-31'])->assertSuccessful();

        Mail::assertSent(UserTrainingStatistics::class, 3); // $this->trainer (null preferences), $stored, $on
        Mail::assertSent(UserTrainingStatistics::class, fn ($m) => $m->hasTo($this->trainer->email));
        Mail::assertSent(UserTrainingStatistics::class, fn ($m) => $m->hasTo($stored->email));
        Mail::assertSent(UserTrainingStatistics::class, fn ($m) => $m->hasTo($on->email));
        Mail::assertNotSent(UserTrainingStatistics::class, fn ($m) => $m->hasTo($out->email));
    }
}
