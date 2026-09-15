<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationLevel;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function create(): View
    {
        return view('admin.organizations.create', [
            'organizations' => Organization::orderBy('name')->get(),
            'levels' => OrganizationLevel::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Organization::create($validated);

        return redirect()->route('admin.dashboard')->with('status', 'Organisation créée.');
    }

    public function edit(Organization $organization): View
    {
        return view('admin.organizations.edit', [
            'organization' => $organization,
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'responsable_first_name' => ['required', 'string', 'max:255'],
            'responsable_last_name' => ['required', 'string', 'max:255'],
            'responsable_email' => ['required', 'email', 'max:255', 'unique:organizations,responsable_email,'.$organization->id],
            'address' => ['nullable', 'string', 'max:255'],
            'business_number' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $organization->update($validated);

        return redirect()->route('admin.dashboard')->with('status', 'Organisation mise à jour.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'level' => ['required', 'string', 'in:'.implode(',', array_column(OrganizationLevel::cases(), 'value'))],
            'parent_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'responsable_first_name' => ['required', 'string', 'max:255'],
            'responsable_last_name' => ['required', 'string', 'max:255'],
            'responsable_email' => ['required', 'email', 'max:255', 'unique:organizations,responsable_email'],
        ]);

        $level = OrganizationLevel::from($validated['level']);
        $parent = $validated['parent_id'] ? Organization::find($validated['parent_id']) : null;

        if ($level === OrganizationLevel::Provincial && $parent !== null) {
            throw ValidationException::withMessages(['parent_id' => 'Une organisation provinciale ne peut pas avoir de parent.']);
        }

        if ($level !== OrganizationLevel::Provincial && ($parent === null || $parent->level->childLevel() !== $level)) {
            throw ValidationException::withMessages(['parent_id' => 'Le parent choisi ne correspond pas au niveau sélectionné.']);
        }

        $validated['level'] = $level;

        return $validated;
    }
}
