<?php

namespace App\Http\Controllers\Bottin;

use App\Http\Controllers\Controller;
use App\Support\ViewerScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationDirectoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();

        [$scopedOrganizations] = ViewerScope::resolve();

        $organizations = $scopedOrganizations
            ->when($query !== '', fn ($organizations) => $organizations->filter(
                fn ($organization) => str_contains(mb_strtolower($organization->name), mb_strtolower($query))
            ))
            ->sortBy('name');

        return view('bottin.organizations', [
            'organizations' => $organizations,
            'query' => $query,
        ]);
    }
}
