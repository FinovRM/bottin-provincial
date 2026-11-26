<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationGroup;
use App\Models\AllowedRole;
use App\Models\MemberRole;
use App\Models\MinimumRole;
use App\Models\Organization;
use App\Support\BottinValidation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PropertiesController extends Controller
{
    public function show(): View
    {
        /** @var Organization $organization */
        $organization = Auth::user()->load('children.responsables', 'memberRoles.member', 'parent.minimumRoles', 'responsables');

        $missingRoles = $organization->parent
            ? $organization->parent->minimumRoles->where('group', $organization->group)->pluck('name')
                ->diff($organization->memberRoles->pluck('role'))
                ->sort()
                ->values()
            : collect();

        return view('dashboard.properties', [
            'organization' => $organization,
            'currentResponsable' => $organization->currentResponsable(),
            'missingRoles' => $missingRoles,
            'groups' => OrganizationGroup::cases(),
            'rolesByGroup' => $organization->canCreateChildren() ? $this->rolesByGroup($organization) : [],
            'isValid' => BottinValidation::isValid($organization),
        ]);
    }

    /**
     * The responsable confirms the data of their bottin is accurate. Changing
     * the data never does this by itself.
     */
    public function validateBottin(Request $request): RedirectResponse
    {
        $request->validate(['confirmed' => ['accepted']], [
            'confirmed.accepted' => 'Cochez la case pour confirmer que les données sont exactes.',
        ]);

        /** @var Organization $organization */
        $organization = Auth::user();

        // Not a change of data: "Mise à jour" keeps its date.
        $organization->timestamps = false;
        $organization->forceFill([
            'validated_at' => now(),
            'validated_by' => $organization->currentResponsable()?->name,
        ])->saveQuietly();

        return redirect()->route('dashboard.properties')->with('status', 'Merci! Les données de votre bottin sont validées.');
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
