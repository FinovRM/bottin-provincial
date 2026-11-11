<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationLevel;
use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Organization;
use App\Models\PersonalFilter;
use App\Support\ViewerScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        // Only members reach this page: responsables stay on their organization's properties.
        /** @var Member $member */
        $member = Auth::guard('member')->user();
        $primaryRole = $member->primaryRole();

        $coordinates = [
            'name' => $member->name,
            'role' => $primaryRole?->role ?? '—',
            'organization' => $primaryRole?->organization->name ?? '—',
            'email' => $member->email,
            'cell_phone' => $member->cell_phone,
            'extension' => $member->extension,
        ];

        [$scopedOrganizations] = ViewerScope::resolve();

        $allLocals = $scopedOrganizations->where('level', OrganizationLevel::Local);
        $locals = $allLocals->sortBy('name');

        $regionIdsWithLocals = $allLocals->pluck('parent_id')->filter()->unique();
        $regions = $scopedOrganizations->where('level', OrganizationLevel::Regional)
            ->merge(Organization::whereIn('id', $regionIdsWithLocals)->get())
            ->unique('id')
            ->sortBy('name');

        // Each role maps to the regions where it's held, in the region itself or one of its locals.
        $organizationRegionIds = $scopedOrganizations->mapWithKeys(fn (Organization $organization) => [
            $organization->id => match ($organization->level) {
                OrganizationLevel::Regional => $organization->id,
                OrganizationLevel::Local => $organization->parent_id,
                default => null,
            },
        ]);

        $roleRegionIds = MemberRole::whereIn('organization_id', $scopedOrganizations->pluck('id'))
            ->orderBy('role')
            ->get(['organization_id', 'role'])
            ->groupBy('role')
            ->map(fn ($memberRoles) => $memberRoles
                ->map(fn (MemberRole $memberRole) => $organizationRegionIds->get($memberRole->organization_id))
                ->filter()->unique()->values());

        $roles = $roleRegionIds->keys();

        return view('profile.index', [
            'coordinates' => $coordinates,
            'regions' => $regions,
            'locals' => $locals,
            'roles' => $roles,
            'roleRegionIds' => $roleRegionIds,
            'personalFilters' => ViewerScope::principal()->personalFilters()->orderBy('name')->get(),
            'identity' => ViewerScope::identity(),
        ]);
    }

    public function storeFilter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'local_ids' => ['array'],
            'local_ids.*' => ['integer'],
            'roles' => ['array'],
            'roles.*' => ['string'],
        ]);

        // The region search only narrows the choices shown; it isn't saved.
        ViewerScope::principal()->personalFilters()->create([...$validated, 'region_ids' => []]);

        return redirect()->route('profile')->with('status', 'Filtre ajouté.');
    }

    public function updateFilter(Request $request, PersonalFilter $personalFilter): RedirectResponse
    {
        $principal = ViewerScope::principal();

        abort_unless(
            $personalFilter->filterable_type === $principal::class && $personalFilter->filterable_id === $principal->id,
            403
        );

        $validated = $request->validate([
            'local_ids' => ['array'],
            'local_ids.*' => ['integer'],
            'roles' => ['array'],
            'roles.*' => ['string'],
        ]);

        $personalFilter->update([
            'region_ids' => [],
            'local_ids' => $validated['local_ids'] ?? [],
            'roles' => $validated['roles'] ?? [],
        ]);

        return redirect()->route('profile')->with('status', 'Filtre mis à jour.');
    }

    public function destroyFilter(PersonalFilter $personalFilter): RedirectResponse
    {
        $principal = ViewerScope::principal();

        abort_unless(
            $personalFilter->filterable_type === $principal::class && $personalFilter->filterable_id === $principal->id,
            403
        );

        $personalFilter->delete();

        return redirect()->route('profile')->with('status', 'Filtre supprimé.');
    }
}
