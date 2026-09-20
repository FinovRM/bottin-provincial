<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var Organization $authOrganization */
        $authOrganization = Auth::user();

        $query = $request->string('q')->trim()->toString();

        return view('dashboard.organizations', [
            'organizations' => $this->filteredOrganizations($authOrganization, $query),
            'query' => $query,
            'identity' => $authOrganization->identity(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        /** @var Organization $authOrganization */
        $authOrganization = Auth::user();

        $organizations = $this->filteredOrganizations($authOrganization, $request->string('q')->trim()->toString());

        return response()->streamDownload(function () use ($organizations) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Organisation', 'Niveau', 'Nom légal', 'Adresse', "N° d'entreprise", 'Site web', 'Responsable', 'Courriel']);

            foreach ($organizations as $organization) {
                fputcsv($handle, [
                    $organization->name,
                    $organization->level->label(),
                    $organization->legal_name ?: '',
                    $organization->fullPostalAddress() ?? '',
                    $organization->business_number ?: '',
                    $organization->website ?: '',
                    $organization->responsable_name,
                    $organization->responsable_email,
                ]);
            }

            fclose($handle);
        }, 'organisations.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return Collection<int, Organization>
     */
    private function filteredOrganizations(Organization $authOrganization, string $query): Collection
    {
        return $authOrganization->visibleOrganizations()
            ->when($query !== '', fn ($organizations) => $organizations->filter(
                fn ($organization) => str_contains(mb_strtolower($organization->name), mb_strtolower($query))
            ))
            ->sortBy('name');
    }
}
