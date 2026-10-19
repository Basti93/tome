<?php

namespace App\Console\Commands;

use App\Services\RetentionService;
use Illuminate\Console\Command;

class RetentionCommand extends Command
{
    protected $signature = 'tome:retention {--dry-run : Only report what would be deleted}';

    protected $description = 'Delete personal data that is no longer needed (inactive users, old participations, deleted trainings, tokens, auth logs). Prints counts only.';

    public function handle(RetentionService $retention): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $counts = $retention->run($dryRun);

        $this->info($dryRun ? 'Retention dry run, nothing was changed:' : 'Retention run finished:');
        foreach ($counts as $what => $count) {
            $this->line(sprintf('  %-20s %d', $what, $count));
        }

        return self::SUCCESS;
    }
}
