<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationLevel;
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
        $regionId = $request->string('region_id')->trim()->toString();
        $localId = $request->string('local_id')->trim()->toString();
        $role = $request->string('role')->trim()->toString();

        $organizations = Organization::orderBy('name')->get();

        $memberRoles = MemberRole::with(['member', 'organization'])
            ->when($query !== '', fn ($memberRoles) => $memberRoles->where(function ($memberRoles) use ($query) {
                $memberRoles->where('role', 'like', "%{$query}%")
                    ->orWhereHas('member', function ($members) use ($query) {
                        $members->where('name', 'like', "%{$query}%")
                            ->orWhere('email', 'like', "%{$query}%");
                    });
            }))
            ->when($localId !== '', fn ($memberRoles) => $memberRoles->where('organization_id', $localId))
            ->when($localId === '' && $regionId !== '', function ($memberRoles) use ($regionId, $organizations) {
                $regionOrganizationIds = $organizations->where('parent_id', (int) $regionId)->pluck('id')->push((int) $regionId);
                $memberRoles->whereIn('organization_id', $regionOrganizationIds);
            })
            ->when($role !== '', fn ($memberRoles) => $memberRoles->where('role', $role))
            ->get()
            ->sortBy([
                ['organization.name', 'asc'],
                ['role', 'asc'],
                ['member.name', 'asc'],
            ]);

        $regions = $organizations->where('level', OrganizationLevel::Regional)->sortBy('name');
        $allLocals = $organizations->where('level', OrganizationLevel::Local);
        $locals = $allLocals
            ->when($regionId !== '', fn ($locals) => $locals->where('parent_id', (int) $regionId))
            ->sortBy('name');

        $roles = MemberRole::distinct()->orderBy('role')->pluck('role');

        return view('admin.members.index', [
            'memberRoles' => $memberRoles,
            'organizations' => $organizations,
            'regions' => $regions,
            'locals' => $locals,
            'roles' => $roles,
            'showRegionFilter' => $regions->count() > 1,
            'showLocalFilter' => $allLocals->count() > 1 || $regions->count() > 0,
            'query' => $query,
            'regionId' => $regionId,
            'localId' => $localId,
            'role' => $role,
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

        Organization::find($validated['organization_id'])->touch();

        return redirect()->route('admin.members.index')->with('status', 'Rôle créé.');
    }

    public function destroy(MemberRole $memberRole): RedirectResponse
    {
        $organization = $memberRole->organization;
        $member = $memberRole->member;
        $memberRole->delete();

        if ($member->roles()->doesntExist()) {
            $member->delete();
        }

        $organization->touch();

        return redirect()->route('admin.members.index')->with('status', 'Rôle supprimé.');
    }
}
