<?php

namespace App\Http\Controllers\Bottin;

use App\Enums\OrganizationLevel;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\ViewerScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationDirectoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();
        $provincialId = $request->string('provincial_id')->trim()->toString();
        $regionalId = $request->string('regional_id')->trim()->toString();
        $localId = $request->string('local_id')->trim()->toString();

        [$scopedOrganizations] = ViewerScope::resolve();

        $allOrganizations = Organization::all()->keyBy('id');

        // Each visible organization's provincial / regional / local ancestor (itself included).
        $lineages = $scopedOrganizations->mapWithKeys(function ($organization) use ($allOrganizations) {
            $lineage = [];

            for ($current = $organization; $current; $current = $allOrganizations->get($current->parent_id)) {
                $lineage[$current->level->value] = $current->id;
            }

            return [$organization->id => $lineage];
        });

        $matches = fn (array $lineage, OrganizationLevel $level, string $id) => $id === '' || ($lineage[$level->value] ?? null) === (int) $id;

        $optionsFor = function (OrganizationLevel $level, array $filters) use ($lineages, $allOrganizations, $matches) {
            return $lineages
                ->filter(fn ($lineage) => collect($filters)->every(fn ($id, $filterLevel) => $matches($lineage, OrganizationLevel::from($filterLevel), $id)))
                ->pluck($level->value)
                ->filter()
                ->unique()
                ->map(fn ($id) => $allOrganizations->get($id))
                ->sortBy('name');
        };

        $provincials = $optionsFor(OrganizationLevel::Provincial, []);

        $regionals = $optionsFor(OrganizationLevel::Regional, ['provincial' => $provincialId]);
        $regionalId = $regionals->contains('id', (int) $regionalId) ? $regionalId : '';

        $locals = $optionsFor(OrganizationLevel::Local, ['provincial' => $provincialId, 'regional' => $regionalId]);
        $localId = $locals->contains('id', (int) $localId) ? $localId : '';

        $organizations = $scopedOrganizations
            ->when($query !== '', fn ($organizations) => $organizations->filter(
                fn ($organization) => str_contains(mb_strtolower($organization->name), mb_strtolower($query))
            ))
            ->filter(fn ($organization) => $matches($lineages[$organization->id], OrganizationLevel::Provincial, $provincialId)
                && $matches($lineages[$organization->id], OrganizationLevel::Regional, $regionalId)
                && $matches($lineages[$organization->id], OrganizationLevel::Local, $localId))
            ->sortBy('name');

        return view('bottin.organizations', [
            'organizations' => $organizations,
            'query' => $query,
            'identity' => ViewerScope::identity(),
            'levelFilters' => [
                ['name' => 'provincial_id', 'label' => 'Provincial', 'options' => $provincials, 'value' => $provincialId],
                ['name' => 'regional_id', 'label' => 'Régional', 'options' => $regionals, 'value' => $regionalId],
                ['name' => 'local_id', 'label' => 'Local', 'options' => $locals, 'value' => $localId],
            ],
        ]);
    }
}
