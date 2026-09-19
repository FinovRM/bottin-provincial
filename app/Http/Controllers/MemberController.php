<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function create(): View
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        Gate::authorize('create', [MemberRole::class, $organization]);

        return view('dashboard.members-create');
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = Auth::user();

        Gate::authorize('create', [MemberRole::class, $organization]);

        $validated = $request->validate([
            'role' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'confirmed'],
            'cell_phone' => ['nullable', 'string', 'max:255'],
        ]);

        $member = Member::findOrCreateByEmail($validated['email'], $validated['name'], $validated['cell_phone'] ?? null);

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
