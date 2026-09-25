<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationLevel;
use App\Models\Organization;
use App\Support\ViewerScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BottinController extends Controller
{
    public function __invoke(): View
    {
        $provincialOrganizations = Organization::where('level', OrganizationLevel::Provincial)
            ->with('children.children')
            ->orderBy('name')
            ->get();

        // Once logged in on the bottin, each organization the visitor may look at opens its members.
        $isVisitor = Auth::guard('member')->check() || Auth::guard('web')->check();

        return view('bottin.index', [
            'provincialOrganizations' => $provincialOrganizations,
            'viewableOrganizationIds' => $isVisitor ? ViewerScope::viewableOrganizationIds() : [],
            'identity' => $isVisitor ? ViewerScope::identity() : null,
        ]);
    }
}
