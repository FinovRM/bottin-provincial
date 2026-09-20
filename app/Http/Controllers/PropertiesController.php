<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PropertiesController extends Controller
{
    public function show(): View
    {
        /** @var Organization $organization */
        $organization = Auth::user()->load('children', 'memberRoles.member', 'parent.minimumRoles');

        $missingRoles = $organization->parent
            ? $organization->parent->minimumRoles->pluck('name')
                ->diff($organization->memberRoles->pluck('role'))
                ->sort()
                ->values()
            : collect();

        return view('dashboard.properties', [
            'organization' => $organization,
            'missingRoles' => $missingRoles,
        ]);
    }
}
