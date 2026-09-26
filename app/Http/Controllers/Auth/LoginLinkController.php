<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Responsable;
use App\Notifications\BottinLoginLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class LoginLinkController extends Controller
{
    /**
     * A courriel matching either a responsable or a member sends a link. The
     * link itself only carries the courriel — which identity(ies) it grants
     * access to is resolved once the link is consumed.
     */
    public function storeBottin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $isEligible = Responsable::where('email', $value)->exists()
                        || Member::where('email', $value)->exists();

                    if (! $isEligible) {
                        $fail("Cette adresse courriel n'est associée à aucun responsable ni membre inscrit au bottin. Seules les adresses des responsables d'organisation et des membres déjà inscrits peuvent recevoir un lien de connexion.");
                    }
                },
            ],
        ]);

        $email = $validated['email'];

        Notification::route('mail', $email)->notify(new BottinLoginLinkNotification($email));

        return redirect()->route('login.sent');
    }
}
