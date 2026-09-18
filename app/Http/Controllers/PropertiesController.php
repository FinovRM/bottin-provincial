<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PropertiesController extends Controller
{
    /**
     * Every visit starts with the responsable declaration — never skipped,
     * even within an already-authenticated session.
     */
    public function show(): View
    {
        return view('auth.responsable-declaration');
    }

    public function confirm(Request $request): View
    {
        $request->validate(['responsable_confirmed' => ['accepted']]);

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
