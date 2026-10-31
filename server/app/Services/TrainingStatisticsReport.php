<?php

namespace App\Services;

use App\Group;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * View model of the monthly trainer statistics e-mail (plain arrays, so the mail can be rendered with fake data).
 *
 * Counted trainings = evaluated, not deleted trainings linked to the group. Attendance = training_participation.attend = 1.
 * Quote = attended / counted trainings of the group in the period (whole percent, null without trainings). There is no
 * join date, so members who joined mid-year appear lower.
 */
class TrainingStatisticsReport
{
    /** Members whose last attendance is more than this many days before the end of the month are listed. */
    public const WATCH_DAYS = 28;

    /**
     * Branch part of the report (identical for all trainers of the branch).
     *
     * @param  Carbon  $from  first day of the reporting month
     * @param  Carbon  $until  last day of the reporting month
     * @param  Carbon|null  $now  send date (absences, not evaluated trainings), default now
     * @return array<string, mixed>
     */
    public function forBranch(int $branchId, Carbon $from, Carbon $until, ?Carbon $now = null): array
    {
        $now = $now ? $now->copy() : now();
        $from = $from->copy()->startOfDay();
        $until = $until->copy()->endOfDay();

        $groups = [];
        foreach (Group::whereBranchId($branchId)->orderBy('name')->get() as $group) {
            $groupReport = $this->forGroup($group, $from, $until, $now);
            if ($groupReport !== null) {
                $groups[] = $groupReport;
            }
        }

        return [
            'trainerName' => null,
            'monthLabel' => $from->format('m.Y'),
            'from' => $from->format('d.m.Y'),
            'until' => $until->format('d.m.Y'),
            'year' => (int) $from->format('Y'),
            'nextMonthLabel' => $from->copy()->addMonthNoOverflow()->locale('de')->translatedFormat('F'),
            'mine' => null,
            'groups' => $groups,
            // not part of the view, needed to compute the per-trainer numbers
            'range' => ['from' => $from->toDateTimeString(), 'until' => $until->toDateTimeString()],
        ];
    }

    /**
     * Adds the personal numbers of one recipient ("Deine Einheiten") to the branch report.
     *
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    public function forTrainer(array $report, User $trainer): array
    {
        $from = Carbon::parse($report['range']['from']);
        $until = Carbon::parse($report['range']['until']);
        $yearStart = $from->copy()->startOfYear();

        $row = DB::table('training_trainer as tt')
            ->join('trainings as t', 't.id', '=', 'tt.training_id')
            ->where('tt.user_id', $trainer->id)
            ->whereNull('t.deleted_at')
            ->where('t.evaluated', 1)
            ->whereBetween('t.start', [$yearStart->toDateTimeString(), $until->toDateTimeString()])
            ->selectRaw('COUNT(DISTINCT CASE WHEN t.start >= ? THEN t.id END) as month_count, COUNT(DISTINCT t.id) as year_count', [$from->toDateTimeString()])
            ->first();

        // reuse of the "Meine Stunden" logic (accounting hours), summed up to the reporting month
        $months = (new TrainerHours)->forYear($trainer, (int) $from->format('Y'))['months'];
        $monthNumber = (int) $from->format('n');
        $hoursMonth = 0.0;
        $hoursYear = 0.0;
        foreach ($months as $m) {
            if ($m['month'] <= $monthNumber) {
                $hoursYear += $m['hours'];
            }
            if ($m['month'] === $monthNumber) {
                $hoursMonth = $m['hours'];
            }
        }

        $report['trainerName'] = $trainer->name;
        $report['mine'] = [
            'monthCount' => (int) $row->month_count,
            'yearCount' => (int) $row->year_count,
            'monthHours' => round($hoursMonth, 2),
            'yearHours' => round($hoursYear, 2),
        ];

        return $report;
    }

    /** @return array<string, mixed>|null null for groups without active members */
    private function forGroup(Group $group, Carbon $from, Carbon $until, Carbon $now): ?array
    {
        $members = User::whereActive(1)
            ->whereHas('groups', fn ($q) => $q->where('groups.id', $group->id))
            ->orderBy('familyName')->orderBy('firstName')
            ->get();
        if ($members->isEmpty()) {
            return null;
        }

        $prevFrom = $from->copy()->subMonthNoOverflow()->startOfMonth();
        $prevUntil = $prevFrom->copy()->endOfMonth();
        $yearStart = $from->copy()->startOfYear();
        $spanStart = $until->copy()->subDays(self::WATCH_DAYS)->startOfDay();
        $lowest = min($yearStart, $prevFrom, $spanStart);
        $f = $from->toDateTimeString();
        $u = $until->toDateTimeString();
        $y = $yearStart->toDateTimeString();
        $pf = $prevFrom->toDateTimeString();
        $pu = $prevUntil->toDateTimeString();

        $inGroup = fn ($q, string $col) => $q->select(DB::raw(1))->from('training_group as tg')
            ->whereColumn('tg.training_id', $col)->where('tg.group_id', $group->id);

        // counted trainings of the group per period (one query)
        $t = DB::table('trainings as t')
            ->whereNull('t.deleted_at')
            ->whereExists(fn ($q) => $inGroup($q, 't.id'))
            ->where('t.start', '>=', $lowest->toDateTimeString())->where('t.start', '<=', $u)
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN t.evaluated = 1 AND t.start BETWEEN ? AND ? THEN t.id END) as month_cnt,
                 COUNT(DISTINCT CASE WHEN t.evaluated = 1 AND t.start BETWEEN ? AND ? THEN t.id END) as year_cnt,
                 COUNT(DISTINCT CASE WHEN t.evaluated = 1 AND t.start BETWEEN ? AND ? THEN t.id END) as prev_cnt,
                 COUNT(DISTINCT CASE WHEN t.evaluated = 1 AND t.start >= ? THEN t.id END) as span_cnt,
                 COUNT(DISTINCT CASE WHEN t.evaluated = 0 AND t.end < ? AND t.start BETWEEN ? AND ? THEN t.id END) as open_cnt',
                [$f, $u, $y, $u, $pf, $pu, $spanStart->toDateTimeString(), $now->toDateTimeString(), $f, $u]
            )->first();
        $monthTrainings = (int) $t->month_cnt;
        $yearTrainings = (int) $t->year_cnt;
        $prevTrainings = (int) $t->prev_cnt;

        // attendances per member (one query)
        $per = DB::table('training_participation as tp')
            ->join('trainings as t', 't.id', '=', 'tp.training_id')
            ->whereNull('t.deleted_at')->where('t.evaluated', 1)->where('tp.attend', 1)
            ->whereIn('tp.user_id', $members->pluck('id')->all())
            ->whereExists(fn ($q) => $inGroup($q, 't.id'))
            ->where('t.start', '<=', $u)
            ->groupBy('tp.user_id')
            ->selectRaw(
                'tp.user_id, MAX(t.start) as last_start,
                 COUNT(DISTINCT CASE WHEN t.start BETWEEN ? AND ? THEN t.id END) as month_cnt,
                 COUNT(DISTINCT CASE WHEN t.start BETWEEN ? AND ? THEN t.id END) as year_cnt,
                 COUNT(DISTINCT CASE WHEN t.start BETWEEN ? AND ? THEN t.id END) as prev_cnt',
                [$f, $u, $y, $u, $pf, $pu]
            )->get()->keyBy('user_id');

        // participants / cancellations of the month (one query)
        $att = DB::table('training_participation as tp')
            ->join('trainings as t', 't.id', '=', 'tp.training_id')
            ->whereNull('t.deleted_at')->where('t.evaluated', 1)
            ->whereIn('tp.user_id', $members->pluck('id')->all()) // a training can belong to several groups
            ->whereExists(fn ($q) => $inGroup($q, 't.id'))
            ->whereBetween('t.start', [$f, $u])
            ->selectRaw('COALESCE(SUM(tp.attend = 1), 0) as present, COALESCE(SUM(tp.attend = 0), 0) as cancelled')
            ->first();

        $today = $now->copy()->startOfDay();
        $rows = [];
        $watch = [];
        $absent = [];
        $monthQuotes = [];
        $yearQuotes = [];
        foreach ($members as $m) {
            $p = $per->get($m->id);
            $monthCount = $p ? (int) $p->month_cnt : 0;
            $yearCount = $p ? (int) $p->year_cnt : 0;
            $prevCount = $p ? (int) $p->prev_cnt : 0;
            $monthQuote = $this->quote($monthCount, $monthTrainings);
            $yearQuote = $this->quote($yearCount, $yearTrainings);
            $prevQuote = $this->quote($prevCount, $prevTrainings);
            $last = $p && $p->last_start ? Carbon::parse($p->last_start)->startOfDay() : null;
            if ($monthQuote !== null) {
                $monthQuotes[] = $monthCount / $monthTrainings * 100;
            }
            if ($yearQuote !== null) {
                $yearQuotes[] = $yearCount / $yearTrainings * 100;
            }

            $rows[] = [
                'name' => $m->name,
                'monthCount' => $monthCount,
                'monthQuote' => $monthQuote,
                'yearCount' => $yearCount,
                'yearQuote' => $yearQuote,
                'trend' => ($monthQuote === null || $prevQuote === null) ? null : ($monthQuote <=> $prevQuote),
                'lastAttendance' => $last?->format('d.m.Y'),
            ];

            $isAbsent = $this->absentOn($m, $today);
            if ($isAbsent) {
                $absent[] = [
                    'name' => $m->name,
                    'until' => $m->absenceEnd ? Carbon::parse($m->absenceEnd)->format('d.m.Y') : null,
                ];
            } elseif ((int) $t->span_cnt > 0) {
                $days = $last ? (int) $last->diffInDays($until->copy()->startOfDay()) : null;
                if ($last === null || $days > self::WATCH_DAYS) {
                    $watch[] = ['name' => $m->name, 'lastAttendance' => $last?->format('d.m.Y'), 'days' => $days];
                }
            }
        }

        usort($rows, fn ($a, $b) => [$b['yearQuote'] ?? -1, $a['name']] <=> [$a['yearQuote'] ?? -1, $b['name']]);
        usort($watch, fn ($a, $b) => [$b['days'] ?? PHP_INT_MAX, $a['name']] <=> [$a['days'] ?? PHP_INT_MAX, $b['name']]);

        return [
            'name' => $group->name,
            'summary' => [
                'monthTrainings' => $monthTrainings,
                'avgParticipants' => $monthTrainings > 0 ? round((int) $att->present / $monthTrainings, 1) : null,
                'avgMonthQuote' => $monthQuotes ? (int) round(array_sum($monthQuotes) / count($monthQuotes)) : null,
                'avgYearQuote' => $yearQuotes ? (int) round(array_sum($yearQuotes) / count($yearQuotes)) : null,
                'cancellations' => (int) $att->cancelled,
                'notEvaluated' => (int) $t->open_cnt,
            ],
            'athletes' => $rows,
            'watch' => $watch,
            'absent' => $absent,
            'birthdays' => $this->birthdays($members, $from),
        ];
    }

    private function quote(int $count, int $trainings): ?int
    {
        return $trainings > 0 ? (int) round($count / $trainings * 100) : null;
    }

    /** Absence covers the date (start and end are optional, but at least one must be set). Never exposes the reason. */
    private function absentOn(User $user, Carbon $day): bool
    {
        if (! $user->absenceStart && ! $user->absenceEnd) {
            return false;
        }
        $start = $user->absenceStart ? Carbon::parse($user->absenceStart)->startOfDay() : null;
        $end = $user->absenceEnd ? Carbon::parse($user->absenceEnd)->startOfDay() : null;

        return ($start === null || $start <= $day) && ($end === null || $end >= $day);
    }

    /**
     * @param  Collection<int, User>  $members
     * @return array<int, array{name: string, date: string, age: int}>
     */
    private function birthdays(Collection $members, Carbon $from): array
    {
        $next = $from->copy()->addMonthNoOverflow();
        $list = [];
        foreach ($members as $m) {
            if (! $m->birthdate) {
                continue;
            }
            $birth = Carbon::parse($m->birthdate);
            if ((int) $birth->format('n') === (int) $next->format('n')) {
                $list[] = [
                    'name' => $m->name,
                    'day' => (int) $birth->format('j'),
                    'date' => $birth->format('d.m.'),
                    'age' => (int) $next->format('Y') - (int) $birth->format('Y'),
                ];
            }
        }
        usort($list, fn ($a, $b) => [$a['day'], $a['name']] <=> [$b['day'], $b['name']]);

        return $list;
    }
}
