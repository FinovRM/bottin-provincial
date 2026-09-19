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
    public function index(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();

        $organizations = Organization::with('parent')
            ->when($query !== '', fn ($organizations) => $organizations->where(function ($organizations) use ($query) {
                $organizations->where('name', 'like', "%{$query}%")
                    ->orWhere('responsable_email', 'like', "%{$query}%");
            }))
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return view('admin.organizations.index', [
            'organizations' => $organizations,
            'query' => $query,
        ]);
    }

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

        return redirect()->route('admin.organizations.index')->with('status', 'Organisation créée.');
    }

    public function edit(Organization $organization): View
    {
        $eligibleParents = Organization::where('id', '!=', $organization->id)
            ->get()
            ->filter(fn (Organization $candidate) => $candidate->level->childLevel() === $organization->level)
            ->sortBy('name')
            ->values();

        return view('admin.organizations.edit', [
            'organization' => $organization,
            'organizations' => $eligibleParents,
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'responsable_name' => ['required', 'string', 'max:255'],
            'responsable_email' => ['required', 'email', 'max:255', 'unique:organizations,responsable_email,'.$organization->id],
            'responsable_cell_phone' => ['required', 'string', 'max:255'],
        ]);

        $parent = $validated['parent_id'] ? Organization::find($validated['parent_id']) : null;

        if ($organization->level === OrganizationLevel::Provincial && $parent !== null) {
            throw ValidationException::withMessages(['parent_id' => 'Une organisation provinciale ne peut pas avoir de parent.']);
        }

        if ($organization->level !== OrganizationLevel::Provincial && ($parent === null || $parent->level->childLevel() !== $organization->level)) {
            throw ValidationException::withMessages(['parent_id' => 'Le parent choisi ne correspond pas au niveau de cette organisation.']);
        }

        $organization->update($validated);

        return redirect()->route('admin.organizations.index')->with('status', 'Organisation mise à jour.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        if ($organization->children()->exists()) {
            return back()->with('status', 'Impossible de supprimer une organisation qui a des organisations sous elle. Supprimez-les d\'abord.');
        }

        $organization->delete();

        return redirect()->route('admin.organizations.index')->with('status', 'Organisation supprimée.');
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
            'responsable_name' => ['required', 'string', 'max:255'],
            'responsable_email' => ['required', 'email', 'max:255', 'confirmed', 'unique:organizations,responsable_email'],
            'responsable_cell_phone' => ['required', 'string', 'max:255'],
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
