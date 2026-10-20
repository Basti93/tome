<?php

namespace App\Console\Commands;

use App\Services\PushNotifier;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Every morning each trainer gets ONE push listing the athletes of their groups (groups of their trainer branches) who
 * have a birthday today. Nothing is sent when there is no birthday. Preference: trainer_birthday (push, default on).
 */
class BirthdayNotifications extends Command
{
    protected $signature = 'notification:birthdays';

    protected $description = 'Tell trainers which athletes of their groups have a birthday today';

    private const MAX_NAMES = 8;

    public function handle(PushNotifier $push): int
    {
        $birthdayUsers = $this->birthdayUsers(Carbon::today());
        if ($birthdayUsers->isEmpty()) {
            $this->info('Keine Geburtstage heute');

            return self::SUCCESS;
        }

        // group id => branch id, user id => group ids of the birthday persons
        $groupBranch = DB::table('groups')->pluck('branch_id', 'id');
        $userGroups = DB::table('user_group')->whereIn('user_id', $birthdayUsers->pluck('id'))->get()->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('group_id')->all());

        $trainers = User::role('trainer')->where('active', true)->with('trainerBranches')->get();
        $sent = 0;
        foreach ($trainers as $trainer) {
            $branchIds = $trainer->trainerBranches->pluck('id')->all();
            $names = $birthdayUsers
                ->filter(fn (User $u) => $u->id !== $trainer->id)
                ->filter(fn (User $u) => collect($userGroups[$u->id] ?? [])->contains(fn ($groupId) => in_array($groupBranch[$groupId] ?? null, $branchIds, true)))
                ->map(fn (User $u) => trim($u->firstName.' '.$u->familyName))
                ->values();
            if ($names->isEmpty()) {
                continue;
            }

            $listed = $names->take(self::MAX_NAMES)->implode(', ');
            $more = $names->count() - self::MAX_NAMES;
            $report = $push->toUsers(
                [$trainer->id],
                $names->count() === 1 ? 'Geburtstag heute' : 'Geburtstage heute',
                'Heute hat Geburtstag: '.$listed.($more > 0 ? ' und '.$more.' weitere' : ''),
                config('app.vue_url').'/#/trainingsPrepare',
                'trainer_birthday',
            );
            if (($report['success'] ?? 0) > 0) {
                $sent++;
            }
        }

        $this->info($birthdayUsers->count().' Geburtstage, '.$sent.' Trainer benachrichtigt');

        return self::SUCCESS;
    }

    /** Active athletes whose birthday is on this day (29 February is celebrated on 28 February in non-leap years). */
    private function birthdayUsers(Carbon $day)
    {
        return User::query()
            ->where('active', true)
            ->whereNotNull('birthdate')
            ->where(function ($q) use ($day) {
                $q->whereRaw('MONTH(birthdate) = ? AND DAY(birthdate) = ?', [$day->month, $day->day]);
                if ($day->month === 2 && $day->day === 28 && ! $day->isLeapYear()) {
                    $q->orWhereRaw('MONTH(birthdate) = 2 AND DAY(birthdate) = 29');
                }
            })
            ->get(['id', 'firstName', 'familyName']);
    }
}
