<?php

namespace App\Http\Controllers\Bottin;

use App\Enums\OrganizationLevel;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use App\Models\PersonalFilter;
use App\Support\ViewerScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();
        $regionId = $request->string('region_id')->trim()->toString();
        $localId = $request->string('local_id')->trim()->toString();
        $role = $request->string('role')->trim()->toString();
        $myDirection = $request->boolean('my_direction');
        $personalFilterId = $request->string('personal_filter_id')->trim()->toString();

        [$scopedOrganizations, $directionOrganizations] = $this->scope();

        $personalFilter = $personalFilterId !== '' ? $this->ownPersonalFilter($personalFilterId) : null;

        $memberRoles = $this->filteredMemberRoles(
            $query, $regionId, $localId, $role, $myDirection, $scopedOrganizations, $directionOrganizations, $personalFilter
        );

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

        $roles = MemberRole::whereIn('organization_id', $scopedOrganizations->pluck('id'))
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
            'personalFilters' => $this->ownPersonalFilters(),
            'query' => $query,
            'regionId' => $regionId,
            'localId' => $localId,
            'role' => $role,
            'myDirection' => $myDirection,
            'personalFilterId' => $personalFilterId,
            'identity' => $this->identity(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $request->string('q')->trim()->toString();
        $regionId = $request->string('region_id')->trim()->toString();
        $localId = $request->string('local_id')->trim()->toString();
        $role = $request->string('role')->trim()->toString();
        $myDirection = $request->boolean('my_direction');
        $personalFilterId = $request->string('personal_filter_id')->trim()->toString();

        [$scopedOrganizations, $directionOrganizations] = $this->scope();

        $personalFilter = $personalFilterId !== '' ? $this->ownPersonalFilter($personalFilterId) : null;

        $memberRoles = $this->filteredMemberRoles(
            $query, $regionId, $localId, $role, $myDirection, $scopedOrganizations, $directionOrganizations, $personalFilter
        );

        return response()->streamDownload(function () use ($memberRoles) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Organisation', 'Fonction', 'Nom', 'Courriel', 'Cellulaire']);

            foreach ($memberRoles as $memberRole) {
                fputcsv($handle, [
                    $memberRole->organization->name,
                    $memberRole->role,
                    $memberRole->member->name,
                    $memberRole->member->email,
                    $memberRole->member->cell_phone ?: '',
                ]);
            }

            fclose($handle);
        }, 'bottin.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return Collection<int, MemberRole>
     */
    private function filteredMemberRoles(
        string $query,
        string $regionId,
        string $localId,
        string $role,
        bool $myDirection,
        Collection $scopedOrganizations,
        Collection $directionOrganizations,
        ?PersonalFilter $personalFilter = null,
    ): Collection {
        $memberRoles = MemberRole::query()
            ->with(['member', 'organization'])
            ->when($query !== '', fn ($memberRoles) => $memberRoles->where(function ($memberRoles) use ($query) {
                $memberRoles->where('role', 'like', "%{$query}%")
                    ->orWhereHas('member', fn ($members) => $members->where('name', 'like', "%{$query}%"));
            }));

        if ($personalFilter) {
            // A personal filter replaces the usual scope entirely, like "ma direction".
            $memberRoles->whereIn('organization_id', $this->personalFilterOrganizationIds($personalFilter, $scopedOrganizations));

            if (! empty($personalFilter->roles)) {
                $memberRoles->whereIn('role', $personalFilter->roles);
            }
        } elseif ($myDirection) {
            // "Ma direction" replaces the usual scope entirely: only the organization(s)
            // immediately above, never same-level-or-below organizations.
            $memberRoles->whereIn('organization_id', $directionOrganizations->pluck('id'));
        } else {
            $memberRoles->whereIn('organization_id', $scopedOrganizations->pluck('id'));

            if ($localId !== '') {
                $memberRoles->where('organization_id', $localId);
            } elseif ($regionId !== '') {
                $regionOrganizationIds = Organization::find($regionId)?->children->pluck('id')->push((int) $regionId) ?? [(int) $regionId];
                $memberRoles->whereIn('organization_id', $regionOrganizationIds);
            }
        }

        $results = $memberRoles
            ->when(! $personalFilter && $role !== '', fn ($memberRoles) => $memberRoles->where('role', $role))
            ->get();

        $permittedRoleNamesByOrganization = $this->permittedRoleNamesByOrganization(
            Organization::whereIn('id', $results->pluck('organization_id')->unique())->get()
        );

        return $results
            ->filter(function (MemberRole $memberRole) use ($permittedRoleNamesByOrganization) {
                $permitted = $permittedRoleNamesByOrganization->get($memberRole->organization_id);

                return $permitted === null || in_array($memberRole->role, $permitted, true);
            })
            ->sortBy([
                ['organization.name', 'asc'],
                ['role', 'asc'],
                ['member.name', 'asc'],
            ]);
    }

    /**
     * For each given organization, the role names currently usable by its own
     * members (its parent's minimum roles union allowed roles, for this
     * organization's own group), or null when unrestricted. The bottin only
     * ever shows members holding a currently permitted role.
     *
     * @param  Collection<int, Organization>  $organizations
     * @return SupportCollection<int, ?array<int, string>>
     */
    private function permittedRoleNamesByOrganization(Collection $organizations): SupportCollection
    {
        $parents = Organization::whereIn('id', $organizations->pluck('parent_id')->filter()->unique())
            ->with('minimumRoles', 'allowedRoles')
            ->get()
            ->keyBy('id');

        return $organizations->mapWithKeys(function (Organization $organization) use ($parents) {
            $parent = $organization->parent_id ? $parents->get($organization->parent_id) : null;

            if (! $parent) {
                return [$organization->id => null];
            }

            $names = $parent->minimumRoles->where('group', $organization->group)->pluck('name')
                ->merge($parent->allowedRoles->where('group', $organization->group)->pluck('name'))
                ->unique();

            return [$organization->id => $names->isNotEmpty() ? $names->all() : null];
        });
    }

    /**
     * @return array<int, int>
     */
    private function personalFilterOrganizationIds(PersonalFilter $personalFilter, Collection $scopedOrganizations): array
    {
        $regionIds = array_map('intval', $personalFilter->region_ids ?? []);
        $localIds = array_map('intval', $personalFilter->local_ids ?? []);

        if (empty($regionIds) && empty($localIds)) {
            return $scopedOrganizations->pluck('id')->all();
        }

        return $scopedOrganizations
            ->filter(fn (Organization $organization) => in_array($organization->id, $localIds, true)
                || in_array($organization->id, $regionIds, true)
                || in_array($organization->parent_id, $regionIds, true))
            ->pluck('id')
            ->all();
    }

    private function ownPersonalFilter(string $id): ?PersonalFilter
    {
        $principal = ViewerScope::principal();

        return PersonalFilter::where('filterable_type', $principal::class)
            ->where('filterable_id', $principal->id)
            ->find($id);
    }

    /**
     * @return Collection<int, PersonalFilter>
     */
    private function ownPersonalFilters(): Collection
    {
        $principal = ViewerScope::principal();

        return PersonalFilter::where('filterable_type', $principal::class)
            ->where('filterable_id', $principal->id)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{0: Collection<int, Organization>, 1: Collection<int, Organization>}
     */
    private function scope(): array
    {
        return ViewerScope::resolve();
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
                    ? "{$primaryRole->organization->responsable_name} ({$primaryRole->organization->responsable_email})"
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
