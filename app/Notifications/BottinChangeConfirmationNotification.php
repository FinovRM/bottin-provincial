<?php

namespace App\Notifications;

use App\Models\BottinRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Sent to the new courriel: following its link confirms both the change and
 * that this courriel is valid.
 */
class BottinChangeConfirmationNotification extends Notification
{
    public function __construct(private readonly BottinRequest $bottinRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('bottin-requests.confirm', now()->addDays(2), ['bottinRequest' => $this->bottinRequest]);

        $mail = (new MailMessage)
            ->subject('Confirmez la modification de vos coordonnées — Bottin de communication')
            ->line("{$this->bottinRequest->name} a demandé que les coordonnées suivantes soient inscrites au bottin :")
            ->line("Courriel : {$this->bottinRequest->new_email}")
            ->line('Téléphone : '.$this->bottinRequest->new_cell_phone.($this->bottinRequest->new_extension ? " poste {$this->bottinRequest->new_extension}" : ''))
            ->line('Rôles visés :');

        foreach ($this->bottinRequest->roles as $role) {
            $mail->line("• {$role['role']} — {$role['organization']}");
        }

        return $mail
            ->action('Confirmer la modification', $url)
            ->line('Ce lien est valide 48 heures. Si vous n\'avez pas fait cette demande, ignorez ce courriel.');
    }
}
