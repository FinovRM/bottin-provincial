<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationGroup;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function create(Request $request): View
    {
        /** @var Organization $parent */
        $parent = Auth::user();

        Gate::authorize('create', [Organization::class, $parent]);

        return view('dashboard.organization-create', [
            'groups' => OrganizationGroup::cases(),
            'defaultGroup' => OrganizationGroup::tryFrom($request->string('group')->toString()) ?? OrganizationGroup::Organisation,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Organization $parent */
        $parent = Auth::user();

        Gate::authorize('create', [Organization::class, $parent]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'group' => ['required', Rule::enum(OrganizationGroup::class)],
            'responsable_name' => ['required', 'string', 'max:255'],
            'responsable_email' => ['required', 'email', 'max:255', 'confirmed'],
        ]);

        $child = $parent->children()->create([
            'name' => $validated['name'],
            'group' => $validated['group'],
            'level' => $parent->level->childLevel(),
        ]);

        $child->responsables()->create([
            'name' => $validated['responsable_name'],
            'email' => $validated['responsable_email'],
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

        $rules = [
            'legal_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'business_number' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ];

        // A child organization's name is set by its parent at creation; only a
        // top-level (parentless) organization can rename itself.
        if ($organization->parent_id === null) {
            $rules['name'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

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
