<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrganizationSwitchController extends Controller
{
    public function index(): View|RedirectResponse
    {
        /** @var Organization $current */
        $current = Auth::user();

        $organizations = $current->organizationsManagedBySameResponsable()->sortBy('name');

        if ($organizations->count() <= 1) {
            return redirect()->route('dashboard.properties');
        }

        return view('dashboard.switch', [
            'organizations' => $organizations,
            'current' => $current,
        ]);
    }

    public function store(Organization $organization): RedirectResponse
    {
        /** @var Organization $current */
        $current = Auth::user();

        abort_unless($organization->responsable_email === $current->responsable_email, 403);

        Auth::guard('web')->login($organization);

        return redirect()->route('dashboard.properties');
    }
}
