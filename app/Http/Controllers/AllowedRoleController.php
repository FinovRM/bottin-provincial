<?php

namespace App\Http\Controllers;

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

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('allowed_roles')->where(fn ($query) => $query->where('organization_id', $organization->id)),
            ],
        ]);

        $organization->allowedRoles()->create($validated);

        return redirect()->route('profile')->with('status', 'Rôle permis ajouté.');
    }

    public function destroy(AllowedRole $allowedRole): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        abort_unless($allowedRole->organization_id === $organization->id, 403);

        $allowedRole->delete();

        return redirect()->route('profile')->with('status', 'Rôle permis supprimé.');
    }
}
