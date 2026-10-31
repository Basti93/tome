<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/** Monthly trainer statistics; the view model comes from App\Services\TrainingStatisticsReport. */
class UserTrainingStatistics extends Mailable
{
    use Queueable;

    /** @param  array<string, mixed>  $report */
    public function __construct(public array $report) {}

    public function build()
    {
        return $this->subject('Monatliche Trainingsstatistik '.$this->report['monthLabel'])
            ->view('email.trainingstatistics', ['report' => $this->report]);
    }
}
