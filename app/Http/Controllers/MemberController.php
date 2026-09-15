<?php

namespace App\Http\Controllers;

use App\Models\Member;
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

        Gate::authorize('create', [Member::class, $organization]);

        $validated = $request->validate([
            'role' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:members,email'],
            'cell_phone' => ['nullable', 'string', 'max:255'],
        ]);

        $organization->members()->create($validated);

        return redirect()->route('dashboard.properties');
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('update', $member);

        $validated = $request->validate([
            'role' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:members,email,'.$member->id],
            'cell_phone' => ['nullable', 'string', 'max:255'],
        ]);

        $member->update($validated);

        return redirect()->route('dashboard.properties');
    }

    public function destroy(Member $member): RedirectResponse
    {
        Gate::authorize('delete', $member);

        $member->delete();

        return redirect()->route('dashboard.properties');
    }
}
