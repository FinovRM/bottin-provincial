<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use App\Rules\NotReservedRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function create(): View
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        Gate::authorize('create', [MemberRole::class, $organization]);

        return view('auth.responsable-declaration');
    }

    public function createConfirmed(Request $request): View
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        Gate::authorize('create', [MemberRole::class, $organization]);

        $request->validate(['responsable_confirmed' => ['accepted']]);

        return view('dashboard.members-create', [
            'roleOptions' => $organization->usableRoleNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        Gate::authorize('create', [MemberRole::class, $organization]);

        $roleOptions = $organization->usableRoleNames();

        $validated = $request->validate([
            'role' => ['required', 'string', 'max:255', new NotReservedRole, ...($roleOptions ? [Rule::in($roleOptions)] : [])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'confirmed'],
            'cell_phone' => ['nullable', 'string', 'max:255'],
            'extension' => ['nullable', 'string', 'max:10'],
        ]);

        $member = Member::findOrCreateByEmail($validated['email'], $validated['name'], $validated['cell_phone'] ?? null, $validated['extension'] ?? null);

        $organization->memberRoles()->create([
            'member_id' => $member->id,
            'role' => $validated['role'],
        ]);

        $organization->touch();

        return redirect()->route('dashboard.properties');
    }

    public function destroy(MemberRole $memberRole): RedirectResponse
    {
        Gate::authorize('delete', $memberRole);

        $organization = $memberRole->organization;
        $member = $memberRole->member;
        $memberRole->delete();

        if ($member->roles()->doesntExist()) {
            $member->delete();
        }

        $organization->touch();

        return redirect()->route('dashboard.properties');
    }
}
