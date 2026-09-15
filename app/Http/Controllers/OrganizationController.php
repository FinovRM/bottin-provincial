<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class OrganizationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        /** @var Organization $parent */
        $parent = Auth::user();

        Gate::authorize('create', [Organization::class, $parent]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'responsable_first_name' => ['required', 'string', 'max:255'],
            'responsable_last_name' => ['required', 'string', 'max:255'],
            'responsable_email' => ['required', 'email', 'max:255', 'unique:organizations,responsable_email'],
        ]);

        $parent->children()->create([
            ...$validated,
            'level' => $parent->level->childLevel(),
        ]);

        return redirect()->route('dashboard');
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('update', $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'responsable_first_name' => ['required', 'string', 'max:255'],
            'responsable_last_name' => ['required', 'string', 'max:255'],
            'responsable_email' => ['required', 'email', 'max:255', 'unique:organizations,responsable_email,'.$organization->id],
        ]);

        $organization->update($validated);

        return redirect()->route('dashboard');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        Gate::authorize('delete', $organization);

        $organization->delete();

        return redirect()->route('dashboard');
    }
}
