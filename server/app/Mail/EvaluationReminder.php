<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EvaluationReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{label: string, url: string}>  $items
     */
    public function __construct(public User $user, public array $items) {}

    public function build()
    {
        return $this->subject('Training noch nicht abgeschlossen')->view('email.evaluation-reminder');
    }
}
