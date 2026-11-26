<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a responsable: someone asks to be removed from the bottin for the
 * role(s) under this responsable's care only.
 */
class BottinRemovalRequestNotification extends Notification
{
    /**
     * @param  array<int, array{organization: string, role: string}>  $roles
     */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly array $roles,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Demande de retrait du bottin')
            ->line('La personne suivante demande que son nom et ses coordonnées soient retirés du bottin :')
            ->line("Nom : {$this->name}")
            ->line("Courriel : {$this->email}")
            ->line('Rôle(s) :');

        foreach ($this->roles as $role) {
            $mail->line("• {$role['role']} — {$role['organization']}");
        }

        return $mail;
    }
}
