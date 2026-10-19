<?php

return [
    'retention' => [
        // The monthly scheduled run of `tome:retention` is off unless explicitly enabled; a manual run
        // (`php artisan tome:retention --dry-run`) always works.
        'enabled' => filter_var(env('RETENTION_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

        // Users without an attended, evaluated participation for this many months (and with an account older
        // than this) are deleted. Trainers and admins are never deleted.
        'inactive_months' => max(1, (int) env('RETENTION_INACTIVE_MONTHS', 24)),
    ],
];
