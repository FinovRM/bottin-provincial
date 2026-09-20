<?php

namespace App\Http\Controllers;

use App\Models\MinimumRole;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MinimumRoleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        abort_unless($organization->canCreateChildren(), 403);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('minimum_roles')->where(fn ($query) => $query->where('organization_id', $organization->id)),
            ],
        ]);

        $organization->minimumRoles()->create($validated);

        return redirect()->route('profile')->with('status', 'Rôle minimum ajouté.');
    }

    public function destroy(MinimumRole $minimumRole): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        abort_unless($minimumRole->organization_id === $organization->id, 403);

        $minimumRole->delete();

        return redirect()->route('profile')->with('status', 'Rôle minimum supprimé.');
    }
}
