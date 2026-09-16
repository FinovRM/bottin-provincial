<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Organization;
use App\Notifications\MemberLoginLinkNotification;
use App\Notifications\OrganizationLoginLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginLinkController extends Controller
{
    public function createBottin(): View
    {
        return view('auth.request-link-bottin');
    }

    /**
     * A courriel matching either a responsable or a member sends a link. A
     * responsable using this door lands on the Bottin, not their editing space.
     */
    public function storeBottin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $organization = Organization::where('responsable_email', $validated['email'])->first();

        if ($organization) {
            $organization->notify(new OrganizationLoginLinkNotification('bottin'));
        } else {
            Member::where('email', $validated['email'])->first()?->notify(new MemberLoginLinkNotification);
        }

        return redirect()->route('login.sent');
    }

    public function createEditeur(): View
    {
        return view('auth.request-link-editeur');
    }

    /**
     * Only a responsable courriel sends a link here.
     */
    public function storeEditeur(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Organization::where('responsable_email', $validated['email'])->first()
            ?->notify(new OrganizationLoginLinkNotification('editeur'));

        return redirect()->route('login.sent');
    }
}
