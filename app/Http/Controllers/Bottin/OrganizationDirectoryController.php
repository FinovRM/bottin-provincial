<?php

namespace App\Http\Controllers\Bottin;

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
        $parentId = $request->string('parent_id')->trim()->toString();
        $childId = $request->string('child_id')->trim()->toString();

        [$scopedOrganizations] = ViewerScope::resolve();

        $parents = Organization::whereKey($scopedOrganizations->pluck('parent_id')->filter()->unique())
            ->orderBy('name')
            ->get();

        $children = $scopedOrganizations
            ->whereNotNull('parent_id')
            ->when($parentId !== '', fn ($children) => $children->where('parent_id', (int) $parentId))
            ->sortBy('name');

        $organizations = $scopedOrganizations
            ->when($query !== '', fn ($organizations) => $organizations->filter(
                fn ($organization) => str_contains(mb_strtolower($organization->name), mb_strtolower($query))
            ))
            ->when($parentId !== '', fn ($organizations) => $organizations->where('parent_id', (int) $parentId))
            ->when($childId !== '', fn ($organizations) => $organizations->where('id', (int) $childId))
            ->sortBy('name');

        return view('bottin.organizations', [
            'organizations' => $organizations,
            'query' => $query,
            'parents' => $parents,
            'parentId' => $parentId,
            'children' => $children,
            'childId' => $childId,
        ]);
    }
}
