<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Notifications\BottinValidationReminderNotification;
use App\Support\BottinValidation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[Signature('bottin:validation-reminders')]
#[Description('Avise les responsables dont le bottin n\'a pas été validé pour la date de contrôle')]
class SendBottinValidationReminders extends Command
{
    public function handle(): int
    {
        $controlDate = BottinValidation::reminderControlDate();

        if (! $controlDate) {
            $this->info('Aucun rappel à envoyer aujourd\'hui.');

            return self::SUCCESS;
        }

        $windowStart = BottinValidation::windowStart($controlDate);

        $organizations = Organization::with('responsables')
            ->where(fn ($query) => $query->whereNull('validated_at')->orWhere('validated_at', '<', $windowStart))
            ->orderBy('name')
            ->get();

        // One message per responsable, listing only their organizations.
        $byResponsable = [];

        foreach ($organizations as $organization) {
            foreach ($organization->responsables as $responsable) {
                $byResponsable[mb_strtolower($responsable->email)][] = $organization->name;
            }
        }

        $label = $controlDate->translatedFormat('j F Y');

        foreach ($byResponsable as $email => $names) {
            Notification::route('mail', $email)->notify(new BottinValidationReminderNotification(array_values(array_unique($names)), $label));
        }

        $this->info(count($byResponsable).' responsable(s) avisé(s).');

        return self::SUCCESS;
    }
}
