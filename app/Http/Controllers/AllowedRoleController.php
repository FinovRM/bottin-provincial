<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationGroup;
use App\Models\AllowedRole;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AllowedRoleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        abort_unless($organization->canCreateChildren(), 403);

        $bag = 'allowed-role-add-'.$request->string('group');

        $validated = $request->validateWithBag($bag, [
            'group' => ['required', Rule::enum(OrganizationGroup::class)],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('allowed_roles')->where(
                    fn ($query) => $query->where('organization_id', $organization->id)->where('group', $request->input('group'))
                ),
            ],
        ]);

        $organization->allowedRoles()->create($validated);

        return redirect()->route('dashboard.properties')->with('status', 'Rôle permis ajouté.');
    }

    public function update(Request $request, AllowedRole $allowedRole): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        abort_unless($allowedRole->organization_id === $organization->id, 403);

        $validated = $request->validateWithBag("allowed-role-{$allowedRole->id}", [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('allowed_roles')
                    ->where(fn ($query) => $query->where('organization_id', $organization->id)->where('group', $allowedRole->group->value))
                    ->ignore($allowedRole),
            ],
        ]);

        $oldName = $allowedRole->name;
        $allowedRole->update($validated);

        $renamed = $validated['name'] !== $oldName
            ? $organization->renameChildMemberRoles($oldName, $validated['name'], $allowedRole->group)
            : 0;

        $status = $renamed > 0
            ? "Rôle permis modifié ({$renamed} membre(s) corrigé(s))."
            : 'Rôle permis modifié.';

        return redirect()->route('dashboard.properties')->with('status', $status);
    }

    public function destroy(AllowedRole $allowedRole): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        abort_unless($allowedRole->organization_id === $organization->id, 403);

        $removed = $organization->removeChildMemberRolesNamed($allowedRole->name, $allowedRole->group);
        $allowedRole->delete();

        $status = $removed > 0
            ? "Rôle permis supprimé ({$removed} membre(s) retiré(s))."
            : 'Rôle permis supprimé.';

        return redirect()->route('dashboard.properties')->with('status', $status);
    }
}
