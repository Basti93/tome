<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UserTrainingStatistics extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $userGroupAttendance;
    public $fromDate;
    public $untilDate;

    /**
     * Create a new message instance.
     *
     * @param User $user
     */
    public function __construct(User $user, $userGroupAttendance, $fromDate, $untilDate)
    {
        $this->user = $user;
        $this->userGroupAttendance = $userGroupAttendance;
        $this->fromDate = $fromDate;
        $this->untilDate = $untilDate;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Monatliche Trainingsstatistik '.date_format($this->fromDate, 'm.Y'))->view('email.trainingstatistics');
    }
}
