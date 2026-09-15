<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
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

        $organization?->notify(new OrganizationLoginLinkNotification);

        return redirect()->route('login.sent');
    }
}
