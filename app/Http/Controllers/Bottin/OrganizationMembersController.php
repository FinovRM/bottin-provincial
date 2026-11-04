<?php

namespace App\Http\Controllers\Bottin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\ViewerScope;
use Illuminate\View\View;

class OrganizationMembersController extends Controller
{
    /**
     * The members of one organization, for the dialog opened from the home
     * page — only for an organization the viewer may look at. Every role is
     * listed, even one no longer among the roles permitted there.
     */
    public function __invoke(Organization $organization): View
    {
        abort_unless(in_array($organization->id, ViewerScope::viewableOrganizationIds(), true), 403);

        $memberRoles = $organization->memberRoles()
            ->with('member')
            ->get()
            ->sortBy([
                ['role', 'asc'],
                ['member.name', 'asc'],
            ]);

        return view('bottin.organization-members', [
            'organization' => $organization,
            'memberRoles' => $memberRoles,
        ]);
    }
}
