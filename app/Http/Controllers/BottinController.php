<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationLevel;
use App\Models\Organization;
use App\Support\ViewerScope;
use App\Support\VisitorIdentities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BottinController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $isResponsable = Auth::guard('web')->check();
        $isMember = Auth::guard('member')->check();

        // A responsable of a chosen organization only works on its properties.
        if ($isResponsable && ! VisitorIdentities::hasNoChosenRole()) {
            return redirect()->route('dashboard.properties');
        }

        $provincialOrganizations = Organization::where('level', OrganizationLevel::Provincial)
            ->with('children.children')
            ->orderBy('name')
            ->get();

        return view('bottin.index', [
            'provincialOrganizations' => $provincialOrganizations,
            // Members open the organizations they may look at; a responsable still
            // picking an organization gets no links.
            'viewableOrganizationIds' => $isMember ? ViewerScope::viewableOrganizationIds() : [],
            'identity' => $isMember || $isResponsable ? ViewerScope::identity() : null,
        ]);
    }
}
