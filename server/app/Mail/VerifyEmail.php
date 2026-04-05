<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationLink;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string $verificationLink
     */
    public function __construct(User $user, string $verificationLink)
    {
        $this->user = $user;
        $this->verificationLink = $verificationLink;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Bitte verifizieren Sie Ihre E-Mail-Adresse')
                    ->view('email.verify-email');
    }
}
