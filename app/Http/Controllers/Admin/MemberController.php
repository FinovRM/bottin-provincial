<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();

        $memberRoles = MemberRole::with(['member', 'organization'])
            ->when($query !== '', fn ($memberRoles) => $memberRoles->where(function ($memberRoles) use ($query) {
                $memberRoles->where('role', 'like', "%{$query}%")
                    ->orWhereHas('member', function ($members) use ($query) {
                        $members->where('name', 'like', "%{$query}%")
                            ->orWhere('email', 'like', "%{$query}%");
                    });
            }))
            ->get()
            ->sortBy('member.name');

        return view('admin.members.index', [
            'memberRoles' => $memberRoles,
            'organizations' => Organization::orderBy('name')->get(),
            'query' => $query,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'role' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'cell_phone' => ['nullable', 'string', 'max:255'],
        ]);

        $member = Member::findOrCreateByEmail($validated['email'], $validated['name'], $validated['cell_phone'] ?? null);

        MemberRole::create([
            'member_id' => $member->id,
            'organization_id' => $validated['organization_id'],
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.members.index')->with('status', 'Rôle créé.');
    }

    public function destroy(MemberRole $memberRole): RedirectResponse
    {
        $member = $memberRole->member;
        $memberRole->delete();

        if ($member->roles()->doesntExist()) {
            $member->delete();
        }

        return redirect()->route('admin.members.index')->with('status', 'Rôle supprimé.');
    }
}
