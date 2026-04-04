<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $resetUrl = env('VUE_APP_API_URL', 'http://localhost:8080') . '/#/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Passwort zurücksetzen')
            ->greeting('Hallo ' . $notifiable->firstName . ',')
            ->line('Sie erhalten diese E-Mail, weil wir eine Anfrage zum Zurücksetzen des Passworts für Ihr Konto erhalten haben.')
            ->action('Passwort zurücksetzen', $resetUrl)
            ->line('Dieser Passwort-Zurücksetzen-Link verfällt in ' . config('auth.passwords.' . $this->getPasswordResetChannelName() . '.expire') . ' Minuten.')
            ->line('Wenn Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren.')
            ->salutation('Mit freundlichen Grüßen, das T.O.M.E. Team');
    }

    /**
     * Get the name of the password reset channel name.
     *
     * @return string
     */
    protected function getPasswordResetChannelName()
    {
        return 'users';
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
