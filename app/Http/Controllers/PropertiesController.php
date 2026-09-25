<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationGroup;
use App\Models\AllowedRole;
use App\Models\MemberRole;
use App\Models\MinimumRole;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PropertiesController extends Controller
{
    public function show(): View
    {
        /** @var Organization $organization */
        $organization = Auth::user()->load('children', 'memberRoles.member', 'parent.minimumRoles');

        $missingRoles = $organization->parent
            ? $organization->parent->minimumRoles->where('group', $organization->group)->pluck('name')
                ->diff($organization->memberRoles->pluck('role'))
                ->sort()
                ->values()
            : collect();

        return view('dashboard.properties', [
            'organization' => $organization,
            'missingRoles' => $missingRoles,
            'groups' => OrganizationGroup::cases(),
            'rolesByGroup' => $organization->canCreateChildren() ? $this->rolesByGroup($organization) : [],
        ]);
    }

    /**
     * For each group, the minimum and allowed roles this organization sets for
     * its children, each with how many child members currently hold it.
     *
     * @return array<string, array{minimumRoles: Collection<int, MinimumRole>, allowedRoles: Collection<int, AllowedRole>}>
     */
    private function rolesByGroup(Organization $organization): array
    {
        $rolesByGroup = [];

        foreach (OrganizationGroup::cases() as $group) {
            $childMemberCountsByRole = MemberRole::whereIn(
                'organization_id',
                $organization->children()->where('group', $group)->pluck('id')
            )
                ->selectRaw('role, count(*) as aggregate')
                ->groupBy('role')
                ->pluck('aggregate', 'role');

            $minimumRoles = $organization->minimumRoles()->where('group', $group)->orderBy('name')->get();
            $allowedRoles = $organization->allowedRoles()->where('group', $group)->orderBy('name')->get();

            $minimumRoles->each(fn (MinimumRole $role) => $role->member_count = $childMemberCountsByRole->get($role->name, 0));
            $allowedRoles->each(fn (AllowedRole $role) => $role->member_count = $childMemberCountsByRole->get($role->name, 0));

            $rolesByGroup[$group->value] = [
                'minimumRoles' => $minimumRoles,
                'allowedRoles' => $allowedRoles,
            ];
        }

        return $rolesByGroup;
    }
}
