<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationLevel;
use App\Models\Organization;
use Illuminate\View\View;

class BottinController extends Controller
{
    public function __invoke(): View
    {
        $provincialOrganizations = Organization::where('level', OrganizationLevel::Provincial)
            ->with('children.children')
            ->orderBy('name')
            ->get();

        return view('bottin.index', [
            'provincialOrganizations' => $provincialOrganizations,
        ]);
    }
}
