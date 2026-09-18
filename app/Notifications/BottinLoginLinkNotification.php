<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class BottinLoginLinkNotification extends Notification
{
    public function __construct(private readonly string $email) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'bottin-login.verify',
            now()->addMinutes(15),
            ['email' => $this->email],
        );

        return (new MailMessage)
            ->subject('Votre lien de connexion — Bottin de communication')
            ->line('Voici votre lien de connexion au Bottin de communication.')
            ->action('Se connecter', $url)
            ->line('Ce lien est valide 15 minutes. Si vous n\'avez pas demandé ce lien, vous pouvez ignorer ce courriel.');
    }
}
