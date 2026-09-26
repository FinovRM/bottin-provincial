<?php

namespace App\Http\Controllers\Bottin;

use App\Enums\OrganizationLevel;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\ViewerScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        return view('bottin.organizations', [
            ...$this->filtered($request),
            'identity' => ViewerScope::identity(),
        ]);
    }

    /**
     * The organizations currently shown (same search and filters), as a CSV file.
     */
    public function export(Request $request): StreamedResponse
    {
        $organizations = $this->filtered($request)['organizations'];

        return response()->streamDownload(function () use ($organizations) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Organisation', 'Niveau', 'Groupe', 'Nom légal', 'Adresse', "N° d'entreprise", 'Site web', 'Responsable(s)', 'Courriel(s)']);

            foreach ($organizations as $organization) {
                fputcsv($handle, [
                    $organization->name,
                    $organization->level->label(),
                    $organization->group->label(),
                    $organization->legal_name ?: '',
                    $organization->fullPostalAddress() ?? '',
                    $organization->business_number ?: '',
                    $organization->website ?: '',
                    $organization->responsables->pluck('name')->implode(' ; '),
                    $organization->responsables->pluck('email')->implode(' ; '),
                ]);
            }

            fclose($handle);
        }, 'organisations.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * The visible organizations matching the search and the regional / local
     * filters, along with those filters' state.
     *
     * @return array<string, mixed>
     */
    private function filtered(Request $request): array
    {
        $query = $request->string('q')->trim()->toString();
        $regionalId = $request->string('regional_id')->trim()->toString();
        // Several local organizations can be picked; a single local_id is still understood.
        $localIds = collect(Arr::wrap($request->input('local_ids')))
            ->push($request->input('local_id'))
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn ($id) => $id !== '')
            ->unique();
        $myDirection = $request->boolean('my_direction');
        $myOrganization = $request->boolean('my_organization');

        [$scopedOrganizations, $directionOrganizations] = ViewerScope::resolve();
        $ownOrganization = ViewerScope::ownOrganization();

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

        $regionals = $optionsFor(OrganizationLevel::Regional, []);
        $regionalId = $regionals->contains('id', (int) $regionalId) ? $regionalId : '';

        $locals = $optionsFor(OrganizationLevel::Local, ['regional' => $regionalId]);
        $localIds = $localIds->filter(fn ($id) => $locals->contains('id', (int) $id))->values();
        $localIdInts = $localIds->map(fn ($id) => (int) $id)->all();

        // "Mon parent" and "Mon organisation" replace the usual scope and its level filters.
        $organizations = ($myDirection || $myOrganization)
            ? Collection::make()
                ->when($myDirection, fn ($organizations) => $organizations->merge($directionOrganizations))
                ->when($myOrganization && $ownOrganization, fn ($organizations) => $organizations->push($ownOrganization))
                ->unique('id')
            : $scopedOrganizations->filter(fn ($organization) => $matches($lineages[$organization->id], OrganizationLevel::Regional, $regionalId)
                && ($localIdInts === [] || in_array($lineages[$organization->id][OrganizationLevel::Local->value] ?? null, $localIdInts, true)));

        $organizations = $organizations
            ->when($query !== '', fn ($organizations) => $organizations->filter(
                fn ($organization) => str_contains(mb_strtolower($organization->name), mb_strtolower($query))
            ))
            ->sortBy('name')
            ->load('responsables');

        return [
            'organizations' => $organizations,
            'query' => $query,
            'myDirection' => $myDirection,
            'myOrganization' => $myOrganization,
            'showMyDirectionFilter' => $directionOrganizations->isNotEmpty(),
            'showMyOrganizationFilter' => $ownOrganization !== null,
            'levelFilters' => [
                ['name' => 'regional_id', 'label' => 'Régional', 'options' => $regionals, 'value' => $regionalId],
                ['name' => 'local_ids', 'label' => 'Local', 'options' => $locals, 'value' => $localIds->all(), 'multiple' => true],
            ],
        ];
    }
}
