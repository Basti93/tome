<?php

namespace App\Console\Commands;

use App\Services\TrainingSeriesGenerator;
use Illuminate\Console\Command;

class TrainingSeriesCommand extends Command
{
    protected $signature = 'training:series';

    protected $description = 'Check all training series and add the trainings for the next 7 days';

    public function handle(TrainingSeriesGenerator $generator)
    {
        $this->info('Created '.$generator->generate().' trainings from series');
    }
}
