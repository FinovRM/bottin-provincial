<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class OrganizationLoginLinkNotification extends Notification
{
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
            'login.consume',
            now()->addMinutes(15),
            ['organization' => $notifiable->getKey()],
        );

        return (new MailMessage)
            ->subject('Votre lien de connexion — Bottin de communication')
            ->line("Voici votre lien de connexion pour {$notifiable->name}.")
            ->action('Se connecter', $url)
            ->line('Ce lien est valide 15 minutes. Si vous n\'avez pas demandé ce lien, vous pouvez ignorer ce courriel.');
    }
}
