<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PropertiesController extends Controller
{
    public function __invoke(): View
    {
        $organization = Auth::user()->load('children', 'memberRoles.member');

        $missingRoles = Role::where('level', $organization->level)
            ->whereNotIn('name', $organization->memberRoles->pluck('role'))
            ->orderBy('name')
            ->pluck('name');

        return view('dashboard.properties', [
            'organization' => $organization,
            'missingRoles' => $missingRoles,
        ]);
    }
}
