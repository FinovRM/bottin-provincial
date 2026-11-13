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
use Illuminate\Validation\Rule;
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

        $choices = $this->filterChoices();

        return view('profile.index', [
            'coordinates' => $coordinates,
            'regions' => $choices['regions'],
            'assistantRegions' => $choices['assistantRegions'],
            'locals' => $choices['locals'],
            'roles' => $choices['roles'],
            'roleOrganizationIds' => $choices['roleOrganizationIds'],
            'personalFilters' => ViewerScope::principal()->personalFilters()->orderBy('name')->get(),
            'identity' => ViewerScope::identity(),
        ]);
    }

    public function storeFilter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            ...$this->choiceRules(),
        ]);

        ViewerScope::principal()->personalFilters()->create($validated);

        return redirect()->route('profile')->with('status', 'Filtre ajouté.');
    }

    public function updateFilter(Request $request, PersonalFilter $personalFilter): RedirectResponse
    {
        $principal = ViewerScope::principal();

        abort_unless(
            $personalFilter->filterable_type === $principal::class && $personalFilter->filterable_id === $principal->id,
            403
        );

        $validated = $request->validate($this->choiceRules());

        $personalFilter->update([
            'region_ids' => $validated['region_ids'] ?? [],
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

    /**
     * The choices a personal filter may hold: only what the visitor's role may see.
     * The assistant's region dropdown also offers the regions above visible locals,
     * to narrow them down, but those regions can't be chosen in the filter itself.
     *
     * @return array{regions: \Illuminate\Support\Collection, assistantRegions: \Illuminate\Support\Collection, locals: \Illuminate\Support\Collection, roles: \Illuminate\Support\Collection, roleOrganizationIds: \Illuminate\Support\Collection}
     */
    private function filterChoices(): array
    {
        [$scopedOrganizations] = ViewerScope::resolve();

        $locals = $scopedOrganizations->where('level', OrganizationLevel::Local)->sortBy('name');
        $regions = $scopedOrganizations->where('level', OrganizationLevel::Regional)->sortBy('name');

        $assistantRegions = $regions
            ->merge(Organization::whereIn('id', $locals->pluck('parent_id')->filter()->unique())->get())
            ->unique('id')
            ->sortBy('name');

        $memberRoles = MemberRole::whereIn('organization_id', $scopedOrganizations->pluck('id'))
            ->get(['role', 'organization_id']);

        return [
            'regions' => $regions,
            'assistantRegions' => $assistantRegions,
            'locals' => $locals,
            'roles' => $memberRoles->pluck('role')->unique()->sort()->values(),
            // Organizations where each role is held, for the selection assistant.
            'roleOrganizationIds' => $memberRoles->groupBy('role')
                ->map(fn ($roles) => $roles->pluck('organization_id')->unique()->values()),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function choiceRules(): array
    {
        $choices = $this->filterChoices();

        return [
            'region_ids' => ['array'],
            'region_ids.*' => ['integer', Rule::in($choices['regions']->pluck('id')->all())],
            'local_ids' => ['array'],
            'local_ids.*' => ['integer', Rule::in($choices['locals']->pluck('id')->all())],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::in($choices['roles']->all())],
        ];
    }
}
