<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Organization;
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
            'email' => ['required', 'email'],
        ]);

        $email = $validated['email'];

        if (Organization::where('responsable_email', $email)->exists() || Member::where('email', $email)->exists()) {
            Notification::route('mail', $email)->notify(new BottinLoginLinkNotification($email));
        }

        return redirect()->route('login.sent');
    }
}
