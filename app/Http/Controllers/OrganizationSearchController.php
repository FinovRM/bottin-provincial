<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrganizationSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();

        $organizations = Auth::user()->visibleOrganizations()
            ->when($query !== '', fn ($organizations) => $organizations->filter(
                fn ($organization) => str_contains(mb_strtolower($organization->name), mb_strtolower($query))
            ))
            ->sortBy('name');

        return view('dashboard.organizations', [
            'organizations' => $organizations,
            'query' => $query,
        ]);
    }
}
