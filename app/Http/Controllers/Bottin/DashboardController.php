<?php

namespace App\Http\Controllers\Bottin;

use App\Enums\OrganizationLevel;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();
        $regionId = $request->string('region_id')->trim()->toString();
        $localId = $request->string('local_id')->trim()->toString();
        $role = $request->string('role')->trim()->toString();
        $myDirection = $request->boolean('my_direction');

        [$scopedOrganizations, $directionOrganizations] = $this->scope();
        $organizationIds = $scopedOrganizations->pluck('id');

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
        // visible on its own — not just regions the viewer sees as an organization.
        $regionIdsWithLocals = $allLocals->pluck('parent_id')->filter()->unique();
        $regions = $scopedOrganizations->where('level', OrganizationLevel::Regional)
            ->merge(Organization::whereIn('id', $regionIdsWithLocals)->get())
            ->unique('id')
            ->sortBy('name');

        $roles = MemberRole::whereIn('organization_id', $organizationIds)
            ->distinct()
            ->orderBy('role')
            ->pluck('role');

        return view('bottin.dashboard', [
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
            'identity' => $this->identity(),
        ]);
    }

    /**
     * @return array{0: Collection<int, Organization>, 1: Collection<int, Organization>}
     */
    private function scope(): array
    {
        if (Auth::guard('member')->check()) {
            /** @var Member $authMember */
            $authMember = Auth::guard('member')->user();

            return [$authMember->visibleOrganizations(), $authMember->directionOrganizations()];
        }

        /** @var Organization $authOrganization */
        $authOrganization = Auth::guard('web')->user();
        $highest = $authOrganization->highestManagedOrganization();

        return [$highest->visibleToMembers(), $highest->directionOrganizations()];
    }

    /**
     * Who is looking at the bottin right now — for the black banner under the menu.
     *
     * @return array{name: string, role: string, organization: string, responsable: string}
     */
    private function identity(): array
    {
        if (Auth::guard('member')->check()) {
            /** @var Member $authMember */
            $authMember = Auth::guard('member')->user();
            $primaryRole = $authMember->primaryRole();

            return [
                'name' => $authMember->name,
                'role' => $primaryRole?->role ?? '—',
                'organization' => $primaryRole?->organization->name ?? '—',
                'responsable' => $primaryRole
                    ? trim("{$primaryRole->organization->responsable_first_name} {$primaryRole->organization->responsable_last_name}")
                    : '—',
            ];
        }

        /** @var Organization $authOrganization */
        $authOrganization = Auth::guard('web')->user();
        $highest = $authOrganization->highestManagedOrganization();

        return [
            ...$authOrganization->identity(),
            'organization' => $highest->name,
            'responsable' => $highest->identity()['responsable'],
        ];
    }
}
