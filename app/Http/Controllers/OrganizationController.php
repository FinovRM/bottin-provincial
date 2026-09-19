<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        /** @var Organization $parent */
        $parent = Auth::user();

        Gate::authorize('create', [Organization::class, $parent]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'responsable_name' => ['required', 'string', 'max:255'],
            'responsable_email' => ['required', 'email', 'max:255', 'unique:organizations,responsable_email'],
        ]);

        $parent->children()->create([
            ...$validated,
            'level' => $parent->level->childLevel(),
        ]);

        return redirect()->route('dashboard.properties');
    }

    public function edit(): View
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        Gate::authorize('update', $organization);

        return view('dashboard.organization-edit', ['organization' => $organization]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('update', $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'business_number' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $organization->update($validated);

        return redirect()->route('dashboard.properties');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        Gate::authorize('delete', $organization);

        $organization->delete();

        return redirect()->route('dashboard.properties');
    }
}
