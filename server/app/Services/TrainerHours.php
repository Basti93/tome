<?php

namespace App\Services;

use App\Training;
use App\TrainingTrainer;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The accounting hours (ÜL-Stunden of 45 minutes) of a trainer. The single source for the accounting export
 * (TrainingTrainerExport) and the "Meine Stunden" overview, so both always show the same numbers:
 * the accounting times of the training_trainer rows (TrainingTrainer::accountingMinutes / accountingHours), counted
 * by the start of the accounting time, trainings that were deleted (SoftDeletes) do not count.
 */
class TrainerHours
{
    /** @return Collection<int, TrainingTrainer> */
    public function entries(User $user, \DateTimeInterface $from, \DateTimeInterface $to): Collection
    {
        return TrainingTrainer::query()
            ->where('user_id', $user->id)
            ->whereBetween('accounting_time_start', [$from, $to])
            ->whereHas('training', fn ($q) => $q->withoutTrashed())
            ->with('training.groups')
            ->orderBy('accounting_time_start')
            ->get();
    }

    /** Hours of one entry exactly as the export shows them (rounded to 2 decimals). */
    public static function rowHours(TrainingTrainer $entry): float
    {
        return round((float) $entry->accountingHours, 2);
    }

    /**
     * @return array{year: int, trainingCount: int, minutes: int, hours: float, months: array<int, array<string, mixed>>, quarters: array<int, array<string, mixed>>, unevaluated: array<int, array<string, mixed>>}
     */
    public function forYear(User $user, int $year): array
    {
        $from = Carbon::create($year, 1, 1, 0, 0, 0);
        $to = Carbon::create($year, 12, 31, 23, 59, 59);

        $months = [];
        foreach (range(1, 12) as $m) {
            $months[$m] = ['month' => $m, 'trainingCount' => 0, 'minutes' => 0, 'hours' => 0.0, 'entries' => []];
        }
        foreach ($this->entries($user, $from, $to) as $entry) {
            $m = (int) $entry->accounting_time_start->format('n');
            $months[$m]['trainingCount']++;
            $months[$m]['minutes'] += (int) $entry->accountingMinutes;
            $months[$m]['hours'] += self::rowHours($entry);
            $months[$m]['entries'][] = [
                'trainingId' => $entry->training_id,
                'start' => $entry->accounting_time_start->format('Y-m-d\TH:i:s'),
                'end' => $entry->accounting_time_end->format('Y-m-d\TH:i:s'),
                'minutes' => (int) $entry->accountingMinutes,
                'hours' => self::rowHours($entry),
                'groups' => $entry->training->groups->pluck('name')->values()->all(),
            ];
        }

        $quarters = [];
        foreach (range(1, 4) as $q) {
            $quarters[$q] = ['quarter' => $q, 'trainingCount' => 0, 'minutes' => 0, 'hours' => 0.0];
        }
        foreach ($months as $m => &$month) {
            $month['hours'] = round($month['hours'], 2);
            $q = intdiv($m - 1, 3) + 1;
            $quarters[$q]['trainingCount'] += $month['trainingCount'];
            $quarters[$q]['minutes'] += $month['minutes'];
            $quarters[$q]['hours'] += $month['hours'];
        }
        unset($month);
        foreach ($quarters as &$quarter) {
            $quarter['hours'] = round($quarter['hours'], 2);
        }
        unset($quarter);

        return [
            'year' => $year,
            'trainingCount' => array_sum(array_column($months, 'trainingCount')),
            'minutes' => array_sum(array_column($months, 'minutes')),
            'hours' => round(array_sum(array_column($months, 'hours')), 2),
            'months' => array_values($months),
            'quarters' => array_values($quarters),
            'unevaluated' => $this->unevaluated($user),
        ];
    }

    /** Past trainings of the trainer that are still not evaluated (their times are missing in the accounting). */
    public function unevaluated(User $user): array
    {
        return Training::query()
            ->whereHas('trainers', fn ($q) => $q->where('users.id', $user->id))
            ->where('evaluated', false)
            ->where('end', '<', now())
            ->with('groups')
            ->orderBy('start')
            ->limit(100)
            ->get()
            ->map(fn (Training $t) => [
                'id' => $t->id,
                'start' => Carbon::parse($t->start)->format('Y-m-d\TH:i:s'),
                'end' => Carbon::parse($t->end)->format('Y-m-d\TH:i:s'),
                'groups' => $t->groups->pluck('name')->values()->all(),
            ])->all();
    }
}
