<?php

namespace App\Services;

use App\Support\ProfileImage;
use App\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Data retention (GDPR): removes people and data that are no longer needed.
 *
 * Rules (all "within" checks are inclusive, i.e. a training exactly on the cut-off still counts as recent):
 *
 * Users are deleted when ALL hold (N = config tome.retention.inactive_months, default 24)
 *  - no attended (attend = 1) participation in an evaluated, non-deleted training within N months
 *  - no trainer assignment on a non-deleted training within 12 months (future trainings count)
 *  - neither role admin nor role trainer
 *  - created_at older than N months, or null
 * together with participations, trainer assignments, group/branch pivots, roles/permissions,
 * notification tokens, training notification markers, auth logs, password reset tokens and the profile image file.
 *
 * Independently
 *  - cancel reasons of participations in trainings older than 12 months are nulled
 *  - participations in trainings older than 36 months are deleted
 *  - trainings flagged as deleted that started more than 12 months ago are hard deleted (with pivots)
 *  - notification tokens not updated for 12 months are deleted
 *  - auth logs older than 90 days are deleted
 *
 * Only user rows and their personal data are removed: groups, branches, trainings (except long deleted ones)
 * and series are never deleted because their members left.
 *
 * Only counts are reported, never names.
 */
class RetentionService
{
    public const USER_TRAINER_MONTHS = 12;

    public const CANCEL_REASON_MONTHS = 12;

    public const PARTICIPATION_MONTHS = 36;

    public const DELETED_TRAINING_MONTHS = 12;

    public const TOKEN_MONTHS = 12;

    public const AUTH_LOG_DAYS = 90;

    private const CHUNK = 500;

    /**
     * @return array{users: int, cancel_reasons: int, participations: int, trainings: int, notification_tokens: int, auth_logs: int, profile_images: int}
     */
    public function run(bool $dryRun = false, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        $counts = [
            'users' => 0,
            'cancel_reasons' => 0,
            'participations' => 0,
            'trainings' => 0,
            'notification_tokens' => 0,
            'auth_logs' => 0,
            'profile_images' => 0,
        ];
        $imagesToDelete = [];

        DB::transaction(function () use ($dryRun, $now, &$counts, &$imagesToDelete) {
            // All counts are taken before anything is deleted, so a dry run reports exactly what a real
            // run removes (a row may match several rules, e.g. an old participation of a deleted user).
            $userIds = $this->userCandidates($now)->pluck('users.id')->map(fn ($id) => (int) $id)->all();
            $counts['users'] = count($userIds);
            $imagesToDelete = [];
            foreach (array_chunk($userIds, self::CHUNK) as $chunk) {
                $names = DB::table('users')->whereIn('id', $chunk)->whereNotNull('profile_image_name')->pluck('profile_image_name');
                array_push($imagesToDelete, ...$names->filter(fn ($name) => ProfileImage::isValidName($name))->values()->all());
            }
            $counts['profile_images'] = count($imagesToDelete);

            // cancel reasons of participations that are not deleted by the 36 month rule anyway
            $cancelReasons = $this->participationsOlderThan($now->subMonths(self::CANCEL_REASON_MONTHS))
                ->where('trainings.start', '>=', $now->subMonths(self::PARTICIPATION_MONTHS))
                ->whereNotNull('training_participation.cancelreason');
            $oldParticipations = $this->participationsOlderThan($now->subMonths(self::PARTICIPATION_MONTHS));
            $trainingIds = DB::table('trainings')->whereNotNull('deleted_at')
                ->where('start', '<', $now->subMonths(self::DELETED_TRAINING_MONTHS))->pluck('id')->all();
            $tokens = DB::table('user_notification_token')->where('updated_at', '<', $now->subMonths(self::TOKEN_MONTHS));
            $logs = DB::table('auth_logs')->where('created_at', '<', $now->subDays(self::AUTH_LOG_DAYS));

            $counts['cancel_reasons'] = (clone $cancelReasons)->count();
            $counts['participations'] = (clone $oldParticipations)->count();
            $counts['trainings'] = count($trainingIds);
            $counts['notification_tokens'] = (clone $tokens)->count();
            $counts['auth_logs'] = (clone $logs)->count();

            if ($dryRun) {
                return;
            }

            $cancelReasons->update(['training_participation.cancelreason' => null]);
            $oldParticipations->delete();

            foreach (array_chunk($trainingIds, self::CHUNK) as $chunk) {
                foreach (['training_participation', 'training_trainer', 'training_group', 'training_content', 'user_training_notification'] as $pivot) {
                    DB::table($pivot)->whereIn('training_id', $chunk)->delete();
                }
                DB::table('trainings')->whereIn('id', $chunk)->delete();
            }

            $tokens->delete();
            $logs->delete();

            foreach (array_chunk($userIds, self::CHUNK) as $chunk) {
                $this->deleteUsers($chunk);
            }
        });

        // files cannot be rolled back, so they are only removed once the transaction committed
        if (! $dryRun) {
            foreach ($imagesToDelete as $name) {
                ProfileImage::deleteIfValid($name);
            }
        }

        return $counts;
    }

    private function userCandidates(CarbonImmutable $now): Builder
    {
        $inactiveMonths = max(1, (int) config('tome.retention.inactive_months', 24));
        $participationCutoff = $now->subMonths($inactiveMonths);
        $trainerCutoff = $now->subMonths(self::USER_TRAINER_MONTHS);
        $createdCutoff = $now->subMonths($inactiveMonths);

        return DB::table('users')
            ->where(function ($query) use ($createdCutoff) {
                $query->whereNull('users.created_at')->orWhere('users.created_at', '<', $createdCutoff);
            })
            ->whereNotExists(function ($query) use ($participationCutoff) {
                $query->select(DB::raw(1))->from('training_participation')
                    ->join('trainings', 'trainings.id', '=', 'training_participation.training_id')
                    ->whereColumn('training_participation.user_id', 'users.id')
                    ->where('training_participation.attend', 1)
                    ->where('trainings.evaluated', 1)
                    ->whereNull('trainings.deleted_at')
                    ->where('trainings.start', '>=', $participationCutoff);
            })
            ->whereNotExists(function ($query) use ($trainerCutoff) {
                $query->select(DB::raw(1))->from('training_trainer')
                    ->join('trainings', 'trainings.id', '=', 'training_trainer.training_id')
                    ->whereColumn('training_trainer.user_id', 'users.id')
                    ->whereNull('trainings.deleted_at')
                    ->where('trainings.start', '>=', $trainerCutoff);
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', User::class)
                    ->whereIn('roles.name', ['admin', 'trainer']);
            });
    }

    /** Participation rows of trainings that started before the cut-off. */
    private function participationsOlderThan(CarbonImmutable $cutoff): Builder
    {
        return DB::table('training_participation')
            ->join('trainings', 'trainings.id', '=', 'training_participation.training_id')
            ->where('trainings.start', '<', $cutoff);
    }

    /** @param  array<int, int>  $ids */
    private function deleteUsers(array $ids): void
    {
        DB::table('training_participation')->whereIn('user_id', $ids)->delete();
        DB::table('training_trainer')->whereIn('user_id', $ids)->delete();
        DB::table('user_group')->whereIn('user_id', $ids)->delete();
        DB::table('trainer_branch')->whereIn('user_id', $ids)->delete();
        DB::table('user_notification_token')->whereIn('user_id', $ids)->delete();
        DB::table('user_training_notification')->whereIn('user_id', $ids)->delete();
        DB::table('auth_logs')->whereIn('user_id', $ids)->delete();
        DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $ids)->delete();
        DB::table('model_has_permissions')->where('model_type', User::class)->whereIn('model_id', $ids)->delete();

        $emails = DB::table('users')->whereIn('id', $ids)->whereNotNull('email')->pluck('email')->all();
        if ($emails !== []) {
            DB::table('password_resets')->whereIn('email', $emails)->delete();
            DB::table('password_invites')->whereIn('email', $emails)->delete();
        }

        DB::table('users')->whereIn('id', $ids)->delete();
    }
}
