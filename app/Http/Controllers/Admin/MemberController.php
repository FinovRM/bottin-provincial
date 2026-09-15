<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();

        $members = Member::with('organization')
            ->when($query !== '', fn ($members) => $members->where(function ($members) use ($query) {
                $members->where('name', 'like', "%{$query}%")
                    ->orWhere('role', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            }))
            ->orderBy('name')
            ->get();

        return view('admin.members.index', [
            'members' => $members,
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
            'email' => ['required', 'email', 'max:255', 'unique:members,email'],
            'cell_phone' => ['nullable', 'string', 'max:255'],
        ]);

        Member::create($validated);

        return redirect()->route('admin.members.index')->with('status', 'Membre créé.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return redirect()->route('admin.members.index')->with('status', 'Membre supprimé.');
    }
}
