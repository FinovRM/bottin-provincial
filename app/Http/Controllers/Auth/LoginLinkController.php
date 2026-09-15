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
    public function create(): View
    {
        return view('auth.request-link');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $organization = Organization::where('responsable_email', $validated['email'])->first();

        if ($organization) {
            $organization->notify(new OrganizationLoginLinkNotification);
        } else {
            Member::where('email', $validated['email'])->first()?->notify(new MemberLoginLinkNotification);
        }

        return redirect()->route('login.sent');
    }
}
