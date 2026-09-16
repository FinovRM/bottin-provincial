<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationLevel;
use App\Models\MemberRole;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();
        $regionId = $request->string('region_id')->trim()->toString();
        $localId = $request->string('local_id')->trim()->toString();
        $role = $request->string('role')->trim()->toString();
        $myDirection = $request->boolean('my_direction');

        /** @var Organization $authOrganization */
        $authOrganization = Auth::user();

        $scopedOrganizations = $authOrganization->visibleToMembers();
        $organizationIds = $scopedOrganizations->pluck('id');
        $directionOrganizations = $authOrganization->directionOrganizations();

        $memberRoles = MemberRole::query()
            ->with(['member', 'organization'])
            ->when($query !== '', fn ($memberRoles) => $memberRoles->where(function ($memberRoles) use ($query) {
                $memberRoles->where('role', 'like', "%{$query}%")
                    ->orWhereHas('member', fn ($members) => $members->where('name', 'like', "%{$query}%"));
            }));

        if ($myDirection) {
            // "Ma direction" replaces the usual scope entirely: only the organization(s)
            // immediately above, never same-level-or-below organizations.
            $memberRoles->whereIn('organization_id', $directionOrganizations->pluck('id'));
        } else {
            $memberRoles->whereIn('organization_id', $organizationIds);

            if ($localId !== '') {
                $memberRoles->where('organization_id', $localId);
            } elseif ($regionId !== '') {
                $regionOrganizationIds = Organization::find($regionId)?->children->pluck('id')->push((int) $regionId) ?? [(int) $regionId];
                $memberRoles->whereIn('organization_id', $regionOrganizationIds);
            }
        }

        $memberRoles = $memberRoles
            ->when($role !== '', fn ($memberRoles) => $memberRoles->where('role', $role))
            ->get()
            ->sortBy('member.name');

        $allLocals = $scopedOrganizations->where('level', OrganizationLevel::Local);
        $locals = $allLocals
            ->when($regionId !== '', fn ($locals) => $locals->where('parent_id', (int) $regionId))
            ->sortBy('name');

        // Every region with at least one visible local, plus any region directly
        // visible on its own — not just regions the responsable sees as an organization.
        $regionIdsWithLocals = $allLocals->pluck('parent_id')->filter()->unique();
        $regions = $scopedOrganizations->where('level', OrganizationLevel::Regional)
            ->merge(Organization::whereIn('id', $regionIdsWithLocals)->get())
            ->unique('id')
            ->sortBy('name');

        $roles = MemberRole::whereIn('organization_id', $organizationIds)
            ->distinct()
            ->orderBy('role')
            ->pluck('role');

        return view('dashboard.members', [
            'memberRoles' => $memberRoles,
            'regions' => $regions,
            'locals' => $locals,
            'roles' => $roles,
            'showRegionFilter' => $regions->count() > 1,
            'showLocalFilter' => $allLocals->count() > 1 || $regions->count() > 0,
            'showMyDirectionFilter' => $directionOrganizations->isNotEmpty(),
            'query' => $query,
            'regionId' => $regionId,
            'localId' => $localId,
            'role' => $role,
            'myDirection' => $myDirection,
        ]);
    }
}
