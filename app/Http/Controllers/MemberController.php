<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MemberController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        Gate::authorize('create', [MemberRole::class, $organization]);

        $validated = $request->validate([
            'role' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'cell_phone' => ['nullable', 'string', 'max:255'],
        ]);

        $member = Member::findOrCreateByEmail($validated['email'], $validated['name'], $validated['cell_phone'] ?? null);

        $organization->memberRoles()->create([
            'member_id' => $member->id,
            'role' => $validated['role'],
        ]);

        return redirect()->route('dashboard.properties');
    }

    public function update(Request $request, MemberRole $memberRole): RedirectResponse
    {
        Gate::authorize('update', $memberRole);

        $validated = $request->validate([
            'role' => ['required', 'string', 'max:255'],
        ]);

        $memberRole->update($validated);

        return redirect()->route('dashboard.properties');
    }

    public function destroy(MemberRole $memberRole): RedirectResponse
    {
        Gate::authorize('delete', $memberRole);

        $member = $memberRole->member;
        $memberRole->delete();

        if ($member->roles()->doesntExist()) {
            $member->delete();
        }

        return redirect()->route('dashboard.properties');
    }
}
