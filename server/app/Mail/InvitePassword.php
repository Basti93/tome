<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invitation sent by an admin/trainer: the user sets the first password via the regular
 * password reset link (same token, same frontend route).
 */
class InvitePassword extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public $resetLink;

    public function __construct(User $user, string $resetLink)
    {
        $this->user = $user;
        $this->resetLink = $resetLink;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Einladung zu T.O.M.E. - Passwort festlegen');
    }

    public function content(): Content
    {
        return new Content(view: 'email.invite-password');
    }
}
