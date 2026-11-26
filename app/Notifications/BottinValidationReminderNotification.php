<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a responsable whose bottin(s) weren't validated in time.
 */
class BottinValidationReminderNotification extends Notification
{
    /**
     * @param  array<int, string>  $organizations
     */
    public function __construct(
        public readonly array $organizations,
        public readonly string $controlDate,
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
            ->subject('Validation de votre bottin à faire')
            ->line("Les données des organisations suivantes n'ont pas été validées pour la date de contrôle du {$this->controlDate} :");

        foreach ($this->organizations as $organization) {
            $mail->line("• {$organization}");
        }

        return $mail
            ->line('Veuillez vérifier les données de votre bottin, puis confirmer leur exactitude avec le bouton « Valider mon bottin » de vos propriétés.')
            ->action('Accéder au bottin', route('login'))
            ->line('Ce rappel vous sera envoyé chaque jour tant que la validation ne sera pas faite.');
    }
}
